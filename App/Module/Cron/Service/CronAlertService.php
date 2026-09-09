<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\Entity\CronAgentNodeEntity;
use App\Module\Cron\Entity\CronAgentNodeGroupEntity;
use App\Module\Cron\Entity\CronRobotAlertLogEntity;
use App\Module\Cron\Entity\CronRobotEntity;
use App\Module\Cron\FailureReason;
use App\Module\Cron\Robot\RobotAlertMessage;
use App\Module\Cron\Robot\RobotConfig;
use App\Module\Cron\Robot\RobotSendResult;
use App\Module\Cron\Robot\RobotStrategyFactory;
use App\Module\Cron\Robot\RobotWebhookMask;
use App\Module\Cron\RobotAlertDeliveryStatus;
use App\Module\Cron\RobotAlertType;
use Swoolefy\Worker\Cron\ExecutionStatus;

/**
 * 发送时读节点组当前 robot_id；不写 cron_task_log.robot_id。
 */
class CronAlertService
{
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
            $this->insertLog($execution, $robotId, 0, $status, RobotAlertDeliveryStatus::SKIPPED, self::SKIP_NOT_FOUND, 0, '机器人不存在或已删除');

            return;
        }
        if ((int) ($robot['status'] ?? 0) !== 1) {
            $this->insertLog(
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
        $this->insertLog(
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
     * @return array{robot_id: int, group_id: int, group_name: string, node_name: string}
     */
    private function resolveBinding(int $nodeId): array
    {
        $empty = ['robot_id' => 0, 'group_id' => 0, 'group_name' => '', 'node_name' => ''];
        if ($nodeId <= 0) {
            return $empty;
        }
        $node = CronAgentNodeEntity::withoutTrashed()->where('id', $nodeId)->find();
        if (!$node) {
            return $empty;
        }
        $nodeRow = is_array($node) ? $node : $node->toArray();
        $groupId = (int) ($nodeRow['group_id'] ?? 0);
        $empty['node_name'] = (string) ($nodeRow['node_name'] ?? '');
        if ($groupId <= 0) {
            return $empty;
        }
        $group = CronAgentNodeGroupEntity::query()->where('id', $groupId)->find();
        if (!$group) {
            return $empty;
        }
        $groupRow = is_array($group) ? $group : $group->toArray();

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
    private function insertLog(
        array $execution,
        int $robotId,
        int $platform,
        int $execStatus,
        int $deliveryStatus,
        string $skipReason,
        int $httpStatus,
        string $errorMessage,
    ): void {
        $now = date('Y-m-d H:i:s');
        $row = [
            'execution_id' => (int) ($execution['id'] ?? 0),
            'cron_id' => (int) ($execution['cron_id'] ?? 0),
            'exec_batch_id' => (string) ($execution['exec_batch_id'] ?? ''),
            'robot_id' => $robotId,
            'platform' => $platform,
            'alert_type' => $execStatus === ExecutionStatus::TIMEOUT ? RobotAlertType::TIMEOUT : RobotAlertType::FAILED,
            'status' => $deliveryStatus,
            'skip_reason' => $skipReason,
            'http_status' => $httpStatus,
            'error_message' => $errorMessage,
            'sent_at' => $now,
        ];
        try {
            CronRobotAlertLogEntity::query()->insert($row);
        } catch (\Throwable $e) {
            if ($this->isDuplicateKey($e)) {
                return;
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $robot
     */
    private function isDeleted(array $robot): bool
    {
        $deleted = $robot['deleted_at'] ?? null;

        return $deleted !== null && $deleted !== '' && $deleted !== '0000-00-00 00:00:00';
    }

    private function isDuplicateKey(\Throwable $e): bool
    {
        $code = $e->getCode();
        if ($code === 23000 || $code === '23000' || (int) $code === 1062) {
            return true;
        }
        $msg = $e->getMessage();
        if (str_contains($msg, '1062') || str_contains($msg, 'uk_execution_id') || str_contains($msg, 'Duplicate')) {
            return true;
        }
        $prev = $e->getPrevious();
        if ($prev instanceof \PDOException) {
            return (int) ($prev->errorInfo[1] ?? 0) === 1062;
        }

        return false;
    }
}
