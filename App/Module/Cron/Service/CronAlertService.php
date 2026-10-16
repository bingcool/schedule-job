<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\Repository\CronAgentNodeGroupRepository;
use App\Module\Cron\Repository\CronAgentNodeRepository;
use App\Module\Cron\Entity\CronRobotEntity;
use App\Module\Cron\Repository\CronRobotAlertLogRepository;
use App\Module\Cron\FailureReason;
use App\Module\Cron\NodeLivenessStatus;
use App\Module\Cron\Robot\RobotAlertMessage;
use App\Module\Cron\Robot\RobotConfig;
use App\Module\Cron\Robot\RobotSendResult;
use App\Module\Cron\Robot\RobotStrategyFactory;
use App\Module\Cron\Robot\RobotWebhookMask;
use App\Module\Cron\RobotAlertDeliveryStatus;
use App\Module\Cron\RobotAlertType;
use Swoolefy\Worker\Cron\CronNodeLiveness;
use Swoolefy\Worker\Cron\ExecutionStatus;

/**
 * 发送时读节点组当前 robot_id；不写 cron_task_log.robot_id。
 */
class CronAlertService
{
    private CronAgentNodeRepository $nodeRepository {
        get => $this->nodeRepository ??= new CronAgentNodeRepository();
    }

    private CronAgentNodeGroupRepository $nodeGroupRepository {
        get => $this->nodeGroupRepository ??= new CronAgentNodeGroupRepository();
    }

    private CronRobotAlertLogRepository $alertLogRepository {
        get => $this->alertLogRepository ??= new CronRobotAlertLogRepository();
    }

    private const SKIP_DISABLED = 'robot_disabled';

    private const SKIP_NOT_FOUND = 'robot_not_found';

    private RobotStrategyFactory $strategyFactory {
        get => $this->strategyFactory ??= new RobotStrategyFactory();
    }

    public function send(int $logId): void
    {
        if ($logId <= 0) {
            return;
        }
        $execution = (new ExecutionService())->findById($logId);
        if ($execution === null) {
            return;
        }
        if (trim((string) ($execution['exec_batch_id'] ?? '')) === '') {
            return;
        }
        $status = (int) ($execution['status'] ?? 0);
        if (!in_array($status, [ExecutionStatus::FAILED, ExecutionStatus::TIMEOUT], true)) {
            return;
        }
        if ((string) ($execution['failure_reason'] ?? '') === FailureReason::CANCELLED) {
            return;
        }

        $binding = $this->resolveBinding((int) ($execution['node_id'] ?? 0));
        $robotId = $binding['robot_id'];
        if ($robotId <= 0) {
            return;
        }

        $robot = CronRobotEntity::findRowIncludingDeleted($robotId);
        if ($robot === null || $this->isDeleted($robot)) {
            $this->insertExecutionLog($execution, $robotId, 0, $status, RobotAlertDeliveryStatus::SKIPPED, self::SKIP_NOT_FOUND, 0, '机器人不存在或已删除');

            return;
        }
        if ((int) ($robot['status'] ?? 0) !== 1) {
            $this->insertExecutionLog(
                $execution,
                $robotId,
                (int) ($robot['platform'] ?? 0),
                $status,
                RobotAlertDeliveryStatus::SKIPPED,
                self::SKIP_DISABLED,
                0,
                '机器人已禁用',
            );

            return;
        }

        $config = new RobotConfig(
            $robotId,
            (int) ($robot['platform'] ?? 0),
            (string) ($robot['webhook_url'] ?? ''),
            (string) ($robot['secret'] ?? ''),
        );
        $message = $this->buildMessage($execution, $binding);
        $result = $this->deliver($config, $message);
        $error = RobotWebhookMask::sanitizeError($result->error, $config->webhookUrl, $config->secret);
        $this->insertExecutionLog(
            $execution,
            $robotId,
            $config->platform,
            $status,
            $result->ok ? RobotAlertDeliveryStatus::SUCCESS : RobotAlertDeliveryStatus::FAILED,
            '',
            $result->httpStatus,
            $error,
        );
    }

    /**
     * 心跳边沿：节点从失联恢复或首次上线。CAS 成功后再调用。
     */
    public function sendNodeOnline(int $nodeId, string $lastHeartbeatAt): void
    {
        $this->sendNodeLiveness($nodeId, RobotAlertType::NODE_ONLINE, $lastHeartbeatAt);
    }

    /**
     * 扫描边沿：节点从在线变为失联。CAS 成功后再调用。
     */
    public function sendNodeOffline(int $nodeId, string $lastHeartbeatAt): void
    {
        $this->sendNodeLiveness($nodeId, RobotAlertType::NODE_OFFLINE, $lastHeartbeatAt);
    }

    /**
     * Admin 定时扫描：心跳已过期则 CAS 后推失联告警。
     * 当前心跳仍在线但 liveness_status 未同步的，静默标为在线（不推送）。
     * 从未心跳过的节点界面也是 offline，但不发失联（谈不上掉线）。
     */
    public function scanOfflineNodes(): void
    {
        $now = time();
        foreach ($this->nodeRepository->listLivenessScanRows() as $node) {
            $nodeId = (int) ($node->id ?? 0);
            if ($nodeId <= 0) {
                continue;
            }
            $interval = CronNodeLiveness::normalizeInterval((int) ($node->heartbeat_interval ?? 0));
            $lastHeartbeatAt = $this->heartbeatAtString($node->last_heartbeat_at ?? null);
            $computed = CronNodeLiveness::status(
                $now,
                CronNodeLiveness::parseHeartbeatAt($lastHeartbeatAt !== '' ? $lastHeartbeatAt : null),
                $interval,
            );
            $liveness = (int) ($node->liveness_status ?? NodeLivenessStatus::UNKNOWN);
            if ($computed === CronNodeLiveness::STATUS_ONLINE) {
                if ($liveness !== NodeLivenessStatus::ONLINE) {
                    $this->nodeRepository->casMarkOnline($nodeId);
                }
                continue;
            }
            if ($lastHeartbeatAt === '') {
                continue;
            }
            $dedupKey = $this->nodeDedupKey($nodeId, RobotAlertType::NODE_OFFLINE, $lastHeartbeatAt);
            if ($liveness === NodeLivenessStatus::OFFLINE) {
                if ($this->alertLogRepository->existsByDedupKey($dedupKey)) {
                    continue;
                }
            } else {
                $cutoff = date('Y-m-d H:i:s', $now - CronNodeLiveness::staleAfterSeconds($interval));
                if (!$this->affected($this->nodeRepository->casMarkOfflineIfStale($nodeId, $cutoff))) {
                    continue;
                }
            }
            try {
                $this->sendNodeOffline($nodeId, $lastHeartbeatAt);
            } catch (\Throwable $e) {
                AlertDispatcher::logError($e);
            }
        }
    }

    /**
     * 硬删除早于保留天数的 cron_robot_alert_log。默认保留 7 天。
     *
     * @return int 实际删除行数
     */
    public function purgeExpiredAlertLogs(int $days = 7): int
    {
        if ($days <= 0) {
            return 0;
        }
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        return $this->alertLogRepository->deleteCreatedBefore($cutoff);
    }

    /**
     * @return array{robot_id: int, group_id: int, group_name: string, node_name: string}
     */
    private function resolveBinding(int $nodeId): array
    {
        $empty = ['robot_id' => 0, 'group_id' => 0, 'group_name' => '', 'node_name' => ''];
        if ($nodeId <= 0) {
            return $empty;
        }
        $nodeRow = $this->nodeRepository->findRowByIdNotTrashed($nodeId);
        if ($nodeRow === null) {
            return $empty;
        }
        $groupId = (int) ($nodeRow['group_id'] ?? 0);
        $empty['node_name'] = (string) ($nodeRow['node_name'] ?? '');
        if ($groupId <= 0) {
            return $empty;
        }
        $groupRow = $this->nodeGroupRepository->findRowById($groupId);
        if ($groupRow === null) {
            return $empty;
        }

        return [
            'robot_id' => (int) ($groupRow['robot_id'] ?? 0),
            'group_id' => $groupId,
            'group_name' => (string) ($groupRow['group_name'] ?? ''),
            'node_name' => $empty['node_name'],
        ];
    }

    /**
     * @param array<string, mixed> $execution
     * @param array{robot_id: int, group_id: int, group_name: string, node_name: string} $binding
     */
    private function buildMessage(array $execution, array $binding): RobotAlertMessage
    {
        $taskItem = $execution['task_item'] ?? [];
        if (is_string($taskItem) && $taskItem !== '') {
            $decoded = json_decode($taskItem, true);
            $taskItem = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($taskItem)) {
            $taskItem = [];
        }

        $taskName = '';
        foreach (['cron_name', 'name', 'task_name', 'cron_task'] as $key) {
            if (!empty($taskItem[$key]) && is_scalar($taskItem[$key])) {
                $taskName = (string) $taskItem[$key];
                break;
            }
        }
        $command = '';
        foreach (['command', 'url', 'exec_url'] as $key) {
            if (!empty($taskItem[$key]) && is_scalar($taskItem[$key])) {
                $command = (string) $taskItem[$key];
                break;
            }
        }
        if (function_exists('mb_substr')) {
            $command = mb_substr($command, 0, 200);
        } else {
            $command = substr($command, 0, 200);
        }

        $status = (int) ($execution['status'] ?? 0);
        $durationMs = (int) ($execution['duration_ms'] ?? 0);
        if ($durationMs <= 0) {
            $started = strtotime((string) ($execution['started_at'] ?? '')) ?: 0;
            $finished = strtotime((string) ($execution['finished_at'] ?? '')) ?: 0;
            if ($started > 0 && $finished >= $started) {
                $durationMs = (int) (($finished - $started) * 1000);
            }
        }

        return new RobotAlertMessage(
            isTest: false,
            executionId: (int) ($execution['id'] ?? 0),
            cronId: (int) ($execution['cron_id'] ?? 0),
            execBatchId: (string) ($execution['exec_batch_id'] ?? ''),
            taskName: $taskName,
            nodeId: (int) ($execution['node_id'] ?? 0),
            nodeName: $binding['node_name'],
            nodeGroupId: $binding['group_id'],
            nodeGroupName: $binding['group_name'],
            status: ExecutionStatus::name($status),
            failureReason: (string) ($execution['failure_reason'] ?? ''),
            scheduledAt: (string) ($execution['scheduled_at'] ?? ''),
            startedAt: (string) ($execution['started_at'] ?? ''),
            finishedAt: (string) ($execution['finished_at'] ?? ''),
            durationMs: $durationMs,
            exitCode: isset($execution['exit_code']) && $execution['exit_code'] !== null && $execution['exit_code'] !== ''
                ? (int) $execution['exit_code'] : null,
            httpStatus: isset($execution['http_status']) && $execution['http_status'] !== null && $execution['http_status'] !== ''
                ? (int) $execution['http_status'] : null,
            triggerType: (int) ($execution['trigger_type'] ?? 0),
            command: $command,
            alertType: $status === ExecutionStatus::TIMEOUT ? RobotAlertType::TIMEOUT : RobotAlertType::FAILED,
        );
    }

    private function sendNodeLiveness(int $nodeId, int $alertType, string $lastHeartbeatAt): void
    {
        if ($nodeId <= 0 || !RobotAlertType::isNodeLiveness($alertType)) {
            return;
        }
        $binding = $this->resolveBinding($nodeId);
        $robotId = $binding['robot_id'];
        if ($robotId <= 0) {
            return;
        }
        $dedupKey = $this->nodeDedupKey($nodeId, $alertType, $lastHeartbeatAt);
        $robot = CronRobotEntity::findRowIncludingDeleted($robotId);
        if ($robot === null || $this->isDeleted($robot)) {
            $this->insertAlertRow($dedupKey, 0, $nodeId, $robotId, 0, $alertType, RobotAlertDeliveryStatus::SKIPPED, self::SKIP_NOT_FOUND, 0, '机器人不存在或已删除');

            return;
        }
        if ((int) ($robot['status'] ?? 0) !== 1) {
            $this->insertAlertRow(
                $dedupKey,
                0,
                $nodeId,
                $robotId,
                (int) ($robot['platform'] ?? 0),
                $alertType,
                RobotAlertDeliveryStatus::SKIPPED,
                self::SKIP_DISABLED,
                0,
                '机器人已禁用',
            );

            return;
        }

        $config = new RobotConfig(
            $robotId,
            (int) ($robot['platform'] ?? 0),
            (string) ($robot['webhook_url'] ?? ''),
            (string) ($robot['secret'] ?? ''),
        );
        $message = $this->buildNodeLivenessMessage($nodeId, $alertType, $lastHeartbeatAt, $binding);
        try {
            $result = $this->deliver($config, $message);
        } catch (\Throwable $e) {
            $result = RobotSendResult::fail(0, $e->getMessage());
        }
        $error = RobotWebhookMask::sanitizeError($result->error, $config->webhookUrl, $config->secret);
        $this->insertAlertRow(
            $dedupKey,
            0,
            $nodeId,
            $robotId,
            $config->platform,
            $alertType,
            $result->ok ? RobotAlertDeliveryStatus::SUCCESS : RobotAlertDeliveryStatus::FAILED,
            '',
            $result->httpStatus,
            $error,
        );
    }

    /**
     * @param array{robot_id: int, group_id: int, group_name: string, node_name: string} $binding
     */
    private function buildNodeLivenessMessage(int $nodeId, int $alertType, string $lastHeartbeatAt, array $binding): RobotAlertMessage
    {
        $status = $alertType === RobotAlertType::NODE_ONLINE ? 'ONLINE' : 'OFFLINE';

        return new RobotAlertMessage(
            isTest: false,
            executionId: 0,
            cronId: 0,
            execBatchId: '',
            taskName: '',
            nodeId: $nodeId,
            nodeName: $binding['node_name'],
            nodeGroupId: $binding['group_id'],
            nodeGroupName: $binding['group_name'],
            status: $status,
            failureReason: '',
            scheduledAt: '',
            startedAt: $lastHeartbeatAt,
            finishedAt: date('Y-m-d H:i:s'),
            durationMs: 0,
            exitCode: null,
            httpStatus: null,
            triggerType: 0,
            command: '',
            alertType: $alertType,
            lastHeartbeatAt: $lastHeartbeatAt,
        );
    }

    private function deliver(RobotConfig $config, RobotAlertMessage $message): RobotSendResult
    {
        try {
            return $this->strategyFactory->make($config->platform)->send($config, $message);
        } catch (\Throwable $e) {
            return RobotSendResult::fail(0, RobotWebhookMask::sanitizeError(
                $e->getMessage(),
                $config->webhookUrl,
                $config->secret,
            ));
        }
    }

    /**
     * @param array<string, mixed> $execution
     */
    private function insertExecutionLog(
        array $execution,
        int $robotId,
        int $platform,
        int $execStatus,
        int $deliveryStatus,
        string $skipReason,
        int $httpStatus,
        string $errorMessage,
    ): void {
        $executionId = (int) ($execution['id'] ?? 0);
        $alertType = $execStatus === ExecutionStatus::TIMEOUT ? RobotAlertType::TIMEOUT : RobotAlertType::FAILED;
        $this->insertAlertRow(
            'e:' . $executionId,
            $executionId,
            (int) ($execution['node_id'] ?? 0),
            $robotId,
            $platform,
            $alertType,
            $deliveryStatus,
            $skipReason,
            $httpStatus,
            $errorMessage,
            (int) ($execution['cron_id'] ?? 0),
            (string) ($execution['exec_batch_id'] ?? ''),
        );
    }

    private function insertAlertRow(
        string $dedupKey,
        int $executionId,
        int $nodeId,
        int $robotId,
        int $platform,
        int $alertType,
        int $deliveryStatus,
        string $skipReason,
        int $httpStatus,
        string $errorMessage,
        int $cronId = 0,
        string $execBatchId = '',
    ): void {
        if ($dedupKey === '') {
            return;
        }
        $this->alertLogRepository->insertIgnoringDuplicateKey([
            'dedup_key' => $dedupKey,
            'execution_id' => $executionId,
            'cron_id' => $cronId,
            'node_id' => $nodeId,
            'exec_batch_id' => $execBatchId,
            'robot_id' => $robotId,
            'platform' => $platform,
            'alert_type' => $alertType,
            'status' => $deliveryStatus,
            'skip_reason' => $skipReason,
            'http_status' => $httpStatus,
            'error_message' => $errorMessage,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function nodeDedupKey(int $nodeId, int $alertType, string $lastHeartbeatAt): string
    {
        $hb = CronNodeLiveness::parseHeartbeatAt($lastHeartbeatAt !== '' ? $lastHeartbeatAt : null) ?? time();

        return 'n:' . $nodeId . ':' . $alertType . ':' . $hb;
    }

    private function heartbeatAtString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        $text = trim((string) $value);

        return $text === '0000-00-00 00:00:00' ? '' : $text;
    }

    private function affected(mixed $n): bool
    {
        return $n === true || (int) $n === 1;
    }

    /**
     * @param array<string, mixed> $robot
     */
    private function isDeleted(array $robot): bool
    {
        $deleted = $robot['deleted_at'] ?? null;

        return $deleted !== null && $deleted !== '' && $deleted !== '0000-00-00 00:00:00';
    }

}
