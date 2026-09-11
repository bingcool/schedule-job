<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\Dto\CronTaskManager\ExecutionCancelResultDto;
use App\Module\Cron\Entity\CronTaskLogEntity;
use App\Module\Cron\ExecutionLeaseConfig;
use App\Module\Cron\ExecutionWorkerIdentity;
use App\Module\Cron\FailureReason;
use App\Module\Cron\Kubernetes\KubernetesCrashRecovery;
use Swoolefy\Core\Schedule\ScheduleEvent;
use Swoolefy\Worker\Cron\ExecutionStatus;
use Swoolefy\Worker\Dto\CronUrlTaskMetaDtoWorker;

/**
 * Execution 生命周期唯一入口：upsert + CAS，不把 status 散落到各处直接 UPDATE。
 */
class ExecutionService
{
    /** cron_task_log.message 追加上限（字节），超出保留最新尾部 */
    private const MESSAGE_MAX_BYTES = 60000;

    /**
     * Agent Worker 启动：生成 boot_id，回收过期 RUNNING，启动看护 Tick。
     */
    public function bootAgent(): void
    {
        ExecutionWorkerIdentity::bootId();
        ExecutionRuntimeGuard::boot();
    }

    /**
     * CronManager logWriter 入口。
     *
     * 同一 exec_batch_id 只保留一条 Execution 行（CAS/统计），message 按时间追加成流水链，
     * 不覆盖「开始执行 / PROC_OPEN PID / 重试 / 终态」等关键步骤。
     *
     * @param array<string, mixed> $execution
     */
    public function writeRuntime(
        ScheduleEvent|CronUrlTaskMetaDtoWorker $scheduleTask,
        string $execBatchId,
        string $message,
        int $pid = 0,
        array $execution = [],
    ): void {
        $cronId = (int) $scheduleTask->cron_task_id;
        $row = [
            'cron_id' => $cronId,
            'exec_batch_id' => $execBatchId,
        ];
        if ($pid > 0) {
            $row['pid'] = $pid;
        }

        $taskItem = $scheduleTask->toArray();
        $row['task_item'] = $taskItem;

        foreach ([
            'status', 'trigger_type', 'request_id', 'node_id', 'scheduled_at', 'started_at',
            'finished_at', 'duration_ms', 'exit_code', 'http_status', 'failure_reason',
        ] as $field) {
            if (array_key_exists($field, $execution) && $execution[$field] !== null && $execution[$field] !== '') {
                $row[$field] = $execution[$field];
            }
        }
        if (isset($row['request_id']) && (int) $row['request_id'] <= 0) {
            unset($row['request_id']);
        }

        if ($execBatchId === '') {
            $row['message'] = $this->formatLogLine($message);
            CronTaskLogEntity::query()->insert($row);

            return;
        }

        $existing = $this->findByBatch($cronId, $execBatchId);
        if ($existing === null) {
            $row['message'] = $this->formatLogLine($message);
            $this->insertExecution($scheduleTask, $row, $execution);
            return;
        }

        $merged = $this->mergeMessage((string) ($existing['message'] ?? ''), $message);
        if ($merged !== (string) ($existing['message'] ?? '')) {
            $row['message'] = $merged;
        }

        $this->updateExecution((int) $existing['id'], $existing, $row, $execution, $pid);
    }

    /**
     * 给已有 Execution 追加一条流水，不改 status。超时 SIGTERM/SIGKILL、Cancel 等用。
     */
    public function appendLog(int $logId, string $message): bool
    {
        $row = $this->findById($logId);
        if ($row === null) {
            return false;
        }
        $merged = $this->mergeMessage((string) ($row['message'] ?? ''), $message);
        if ($merged === (string) ($row['message'] ?? '')) {
            return true;
        }
        $n = CronTaskLogEntity::query()
            ->where('id', $logId)
            ->update(['message' => $merged]);

        return $this->affected($n) || $n === 0;
    }

    /**
     * 把执行器产生的运行时元数据并入 `task_item` JSON。
     *
     * 用于 Kubernetes 这类「执行体不在本机」的类型：Job 名 / UID / Pod 名 / 镜像
     * 需要能被崩溃恢复和人工排查读到（方案 §12）。
     *
     * 为什么并进 `task_item` 而不是加独立列：第一版不想为一个执行类型改 5 个列 + 索引；
     * 恢复路径只需要「按 id 读出来」，JSON 足够。P1 再拆列。
     *
     * 只做浅合并，且不动 status/lease：这是纯附加信息，不能影响状态机。
     *
     * @param array<string, mixed> $meta 空值键会被忽略，避免用空串覆盖已写入的 Job 名
     */
    public function mergeTaskItemMeta(int $logId, array $meta): bool
    {
        if ($logId <= 0 || $meta === []) {
            return false;
        }
        $row = $this->findById($logId);
        if ($row === null) {
            return false;
        }

        $taskItem = $row['task_item'] ?? [];
        if (is_string($taskItem)) {
            $decoded = json_decode($taskItem, true);
            $taskItem = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($taskItem)) {
            $taskItem = [];
        }

        $changed = false;
        foreach ($meta as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (($taskItem[$key] ?? null) === $value) {
                continue;
            }
            $taskItem[$key] = $value;
            $changed = true;
        }
        if (!$changed) {
            return true;
        }

        $n = CronTaskLogEntity::query()
            ->where('id', $logId)
            ->update(['task_item' => $taskItem]);

        return $this->affected($n) || $n === 0;
    }

    /**
     * Agent Report 等同路径：已有批次则追加 message，避免冲掉流水。
     */
    public function mergeRuntimeMessage(string $existing, string $incoming): string
    {
        return $this->mergeMessage($existing, $incoming);
    }

    /**
     * RunOnce 消费前闸门：ack=已有终态只确认；defer=租约仍有效；execute=需要跑。
     */
    public function precheckRunOnce(int $requestId): string
    {
        if ($requestId <= 0) {
            return 'execute';
        }
        $row = $this->findLatestByRequestId($requestId);
        if ($row === null) {
            return 'execute';
        }
        $status = (int) ($row['status'] ?? 0);
        if (in_array($status, [
            ExecutionStatus::SUCCESS,
            ExecutionStatus::TIMEOUT,
            ExecutionStatus::CANCELLED,
        ], true)) {
            return 'ack';
        }
        if ($status === ExecutionStatus::RUNNING) {
            if ($this->leaseValid($row)) {
                return 'defer';
            }
            $this->recoverRow($row, FailureReason::WORKER_CRASH);

            return 'execute';
        }
        if ($status === ExecutionStatus::CANCEL_REQUESTED) {
            return 'defer';
        }

        return 'execute';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = CronTaskLogEntity::queryNotDeleted()->where('id', $id)->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }

    public function heartbeat(int $logId, string $owner): bool
    {
        $seconds = $this->leaseDuration();
        $until = date('Y-m-d H:i:s', time() + $seconds);
        $now = date('Y-m-d H:i:s');
        $n = CronTaskLogEntity::query()
            ->where('id', $logId)
            ->whereIn('status', [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED])
            ->where('lease_owner', $owner)
            ->update([
                'heartbeat_at' => $now,
                'lease_until' => $until,
            ]);

        return $this->affected($n);
    }

    public function finishOwned(int $logId, string $owner, int $toStatus, string $reason, string $logMessage = ''): bool
    {
        $row = $this->findById($logId);
        if ($row === null) {
            return false;
        }
        if ((string) ($row['lease_owner'] ?? '') !== $owner) {
            return false;
        }
        $from = (int) ($row['status'] ?? 0);
        if ($from === ExecutionStatus::CANCEL_REQUESTED && $toStatus === ExecutionStatus::TIMEOUT) {
            $toStatus = ExecutionStatus::CANCELLED;
            $reason = FailureReason::CANCELLED;
        }
        if ($from === ExecutionStatus::RUNNING && $toStatus === ExecutionStatus::CANCELLED) {
            $this->transition($logId, ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED, [
                'cancelled_at' => date('Y-m-d H:i:s'),
            ]);
            $from = ExecutionStatus::CANCEL_REQUESTED;
            $row = $this->findById($logId) ?? $row;
        }

        $extra = [
            'failure_reason' => $reason,
            'finished_at' => date('Y-m-d H:i:s'),
        ];
        if ($logMessage !== '') {
            $extra['message'] = $this->mergeMessage((string) ($row['message'] ?? ''), $logMessage);
        }

        return $this->transition($logId, $from, $toStatus, $extra);
    }

    public function recoverExpiredLeases(int $limit = 100): int
    {
        $now = date('Y-m-d H:i:s');
        $rows = CronTaskLogEntity::queryNotDeleted()
            ->whereIn('status', [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED])
            ->whereNotNull('lease_until')
            ->where('lease_until', '<', $now)
            ->order('id', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
        $closed = 0;
        $nodeId = ExecutionWorkerIdentity::nodeId();
        foreach ($rows as $row) {
            if ($this->recoverRow($row, FailureReason::WORKER_CRASH)) {
                $closed++;
                $pid = (int) ($row['pid'] ?? 0);
                if ($nodeId > 0 && (int) ($row['node_id'] ?? 0) === $nodeId && $pid > 0) {
                    ExecutionRuntimeGuard::signalPid($pid, 9);
                }
            }
        }

        return $closed;
    }

    public function requestCancel(int $logId): ExecutionCancelResultDto
    {
        $row = $this->findById($logId);
        if ($row === null) {
            return ExecutionCancelResultDto::alreadyFinished(0, 'unknown');
        }
        $status = (int) ($row['status'] ?? 0);
        $name = ExecutionStatus::name($status);
        if ($status !== ExecutionStatus::RUNNING) {
            return ExecutionCancelResultDto::alreadyFinished($logId, $name);
        }
        $ok = $this->transition($logId, ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED, [
            'cancelled_at' => date('Y-m-d H:i:s'),
            'failure_reason' => FailureReason::CANCELLED,
            'message' => $this->mergeMessage((string) ($row['message'] ?? ''), 'Admin 请求取消，等待 Agent 终止进程'),
        ]);
        if (!$ok) {
            $fresh = $this->findById($logId);
            $freshStatus = (int) ($fresh['status'] ?? $status);

            return ExecutionCancelResultDto::alreadyFinished($logId, ExecutionStatus::name($freshStatus));
        }

        return ExecutionCancelResultDto::accepted($logId, ExecutionStatus::name(ExecutionStatus::CANCEL_REQUESTED));
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function transition(int $id, int $fromStatus, int $toStatus, array $extra = []): bool
    {
        if ($id <= 0 || !$this->isLegalTransition($fromStatus, $toStatus)) {
            return false;
        }
        $data = $extra;
        $data['status'] = $toStatus;
        if ($this->isTerminal($toStatus) && empty($data['finished_at'])) {
            $data['finished_at'] = date('Y-m-d H:i:s');
        }
        $n = CronTaskLogEntity::query()
            ->where('id', $id)
            ->where('status', $fromStatus)
            ->update($data);

        $ok = $this->affected($n);
        if ($ok) {
            AlertDispatcher::dispatchIfNeeded($id, $toStatus);
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function recoverRow(array $row, string $reason): bool
    {
        $id = (int) ($row['id'] ?? 0);
        $from = (int) ($row['status'] ?? 0);
        $now = date('Y-m-d H:i:s');
        $to = ExecutionStatus::FAILED;
        $message = 'RECOVERED ' . $reason;
        try {
            $k8s = (new KubernetesCrashRecovery())->resolve($row);
        } catch (\Throwable) {
            $k8s = null;
        }
        if ($k8s !== null) {
            $to = (int) $k8s['status'];
            $reason = (string) $k8s['failure_reason'];
            $message = 'RECOVERED ' . $k8s['message'];
        }
        if ($from === ExecutionStatus::CANCEL_REQUESTED) {
            $to = ExecutionStatus::CANCELLED;
            $reason = FailureReason::CANCELLED;
        }
        $extra = [
            'finished_at' => $now,
            'message' => $this->mergeMessage((string) ($row['message'] ?? ''), $message),
        ];
        if ($reason !== '') {
            $extra['failure_reason'] = $reason;
        }
        $ok = $this->transition($id, $from, $to, $extra);

        return $ok;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $execution
     */
    private function insertExecution(
        ScheduleEvent|CronUrlTaskMetaDtoWorker $scheduleTask,
        array $row,
        array $execution,
    ): void {
        $status = (int) ($row['status'] ?? ExecutionStatus::RUNNING);
        $row['status'] = $status;
        $nodeId = (int) ($row['node_id'] ?? 0);
        if ($nodeId <= 0) {
            $nodeId = (int) ($scheduleTask->node_id ?? ExecutionWorkerIdentity::nodeId());
            $row['node_id'] = $nodeId;
        }
        if ($status === ExecutionStatus::RUNNING) {
            $this->applyLease($row, $execution, $scheduleTask);
        }
        CronTaskLogEntity::query()->insert($row);
        $saved = $this->findByBatch((int) $row['cron_id'], (string) $row['exec_batch_id']);
        if ($saved && $status === ExecutionStatus::RUNNING) {
            $id = (int) $saved['id'];
            $this->bindScheduleSlot($row, $id);
            ExecutionRuntimeGuard::watch(
                $id,
                (int) ($saved['pid'] ?? 0),
                isset($saved['timeout_at']) ? (string) $saved['timeout_at'] : null,
            );
        }
    }

    /**
     * 调度触发的 RUNNING 行回写 Slot Record；RunOnce 不绑。
     *
     * @param array<string, mixed> $row
     */
    private function bindScheduleSlot(array $row, int $executionId): void
    {
        if ((int) ($row['trigger_type'] ?? 0) !== ExecutionStatus::TRIGGER_SCHEDULER) {
            return;
        }
        $scheduledAt = trim((string) ($row['scheduled_at'] ?? ''));
        if ($scheduledAt === '') {
            return;
        }
        (new CronScheduledTaskRecordService())->bindExecution(
            (int) ($row['cron_id'] ?? 0),
            $scheduledAt,
            $executionId,
        );
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $row
     * @param array<string, mixed> $execution
     */
    private function updateExecution(int $id, array $existing, array $row, array $execution, int $pid): void
    {
        $from = (int) ($existing['status'] ?? 0);
        $hasStatus = array_key_exists('status', $execution) && $execution['status'] !== null && $execution['status'] !== '';
        $to = $hasStatus ? (int) $execution['status'] : $from;

        unset($row['cron_id'], $row['exec_batch_id']);

        if (!$hasStatus) {
            if ($pid > 0) {
                $row['pid'] = $pid;
            }
            unset($row['status']);
            if ($row === []) {
                return;
            }
            $n = CronTaskLogEntity::query()
                ->where('id', $id)
                ->whereIn('status', [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED])
                ->update($row);
            if (!$this->affected($n)) {
                $this->persistMessageIfChanged($id, $existing, $row, $pid);
            }
            if ($pid > 0) {
                ExecutionRuntimeGuard::touchPid($id, $pid);
            }

            return;
        }

        if ($to === $from) {
            if ($to === ExecutionStatus::RUNNING) {
                $this->applyLease($row, $execution, null, $existing);
            }
            unset($row['status']);
            if ($row !== []) {
                CronTaskLogEntity::query()->where('id', $id)->where('status', $from)->update($row);
            }
            if ($pid > 0) {
                ExecutionRuntimeGuard::touchPid($id, $pid);
            }

            return;
        }

        if ($from === ExecutionStatus::CANCEL_REQUESTED && in_array($to, [ExecutionStatus::SUCCESS, ExecutionStatus::FAILED], true)) {
            $to = ExecutionStatus::CANCELLED;
            $row['failure_reason'] = FailureReason::CANCELLED;
        }
        if ($from === ExecutionStatus::TIMEOUT || $from === ExecutionStatus::CANCELLED || $from === ExecutionStatus::SUCCESS) {
            $this->persistMessageIfChanged($id, $existing, $row, $pid);
            return;
        }
        if ($to === ExecutionStatus::FAILED && empty($row['failure_reason'])) {
            $row['failure_reason'] = FailureReason::EXECUTION_ERROR;
        }
        if ($to === ExecutionStatus::TIMEOUT) {
            $row['failure_reason'] = FailureReason::TIMEOUT;
        }
        $this->transition($id, $from, $to, $row);
    }

    /**
     * 已进入终态后仍追加流水（例如超时杀进程后 proc_open 回报的 signal 文案）。
     *
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $row
     */
    private function persistMessageIfChanged(int $id, array $existing, array $row, int $pid): void
    {
        $tail = [];
        if (isset($row['message']) && (string) $row['message'] !== (string) ($existing['message'] ?? '')) {
            $tail['message'] = $row['message'];
        }
        if ($pid > 0 && (int) ($existing['pid'] ?? 0) <= 0) {
            $tail['pid'] = $pid;
        }
        if ($tail === []) {
            return;
        }
        CronTaskLogEntity::query()->where('id', $id)->update($tail);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $execution
     * @param array<string, mixed>|null $existing
     */
    private function applyLease(
        array &$row,
        array $execution,
        ScheduleEvent|CronUrlTaskMetaDtoWorker|null $scheduleTask,
        ?array $existing = null,
    ): void {
        $owner = ExecutionWorkerIdentity::owner();
        $nowTs = time();
        $now = date('Y-m-d H:i:s', $nowTs);
        $row['lease_owner'] = $owner;
        $row['lease_until'] = date('Y-m-d H:i:s', $nowTs + $this->leaseDuration());
        $row['heartbeat_at'] = $now;
        if (empty($row['started_at'])) {
            $row['started_at'] = $now;
        }
        $timeoutSec = (int) ($execution['timeout'] ?? 0);
        if ($timeoutSec <= 0 && $scheduleTask !== null) {
            $arr = $scheduleTask->toArray();
            $timeoutSec = (int) ($arr['timeout'] ?? 0);
        }
        if ($timeoutSec > 0 && empty($existing['timeout_at'])) {
            $startedTs = strtotime((string) $row['started_at']) ?: $nowTs;
            $row['timeout_at'] = date('Y-m-d H:i:s', $startedTs + $timeoutSec);
        }
    }

    /**
     * 按 (cron_id, exec_batch_id) 反查 Execution 行。走 `idx_cron_exec_batch`。
     *
     * 对外公开的原因：Executor 只拿得到 {@see \Swoolefy\Worker\Cron\ExecutionSnapshot}，
     * 里面没有 `cron_task_log.id`。需要 execution_id（写元数据、注册取消句柄）时
     * 只能用这两个字段反查。
     *
     * @return array<string, mixed>|null
     */
    public function findByBatch(int $cronId, string $execBatchId): ?array
    {
        if ($cronId <= 0 || $execBatchId === '') {
            return null;
        }
        $row = CronTaskLogEntity::queryNotDeleted()
            ->where([
                'cron_id' => $cronId,
                'exec_batch_id' => $execBatchId,
            ])
            ->order('id', 'asc')
            ->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findLatestByRequestId(int $requestId): ?array
    {
        $row = CronTaskLogEntity::queryNotDeleted()
            ->where('request_id', $requestId)
            ->order('id', 'desc')
            ->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function leaseValid(array $row): bool
    {
        $until = (string) ($row['lease_until'] ?? '');
        if ($until === '') {
            return false;
        }
        $ts = strtotime($until);

        return $ts !== false && $ts >= time();
    }

    private function isLegalTransition(int $from, int $to): bool
    {
        if ($from === $to) {
            return false;
        }
        $map = [
            ExecutionStatus::RUNNING => [
                ExecutionStatus::SUCCESS,
                ExecutionStatus::FAILED,
                ExecutionStatus::TIMEOUT,
                ExecutionStatus::CANCEL_REQUESTED,
            ],
            ExecutionStatus::CANCEL_REQUESTED => [
                ExecutionStatus::CANCELLED,
                ExecutionStatus::TIMEOUT,
                ExecutionStatus::FAILED,
            ],
        ];

        return in_array($to, $map[$from] ?? [], true);
    }

    private function isTerminal(int $status): bool
    {
        return in_array($status, [
            ExecutionStatus::SUCCESS,
            ExecutionStatus::FAILED,
            ExecutionStatus::SKIPPED,
            ExecutionStatus::TIMEOUT,
            ExecutionStatus::CANCELLED,
        ], true);
    }

    /**
     * 把一次关键步骤追加到 message 流水；相邻重复行不写。
     */
    private function mergeMessage(string $existing, string $incoming): string
    {
        $incoming = trim($incoming);
        if ($incoming === '') {
            return $existing;
        }
        $line = $this->formatLogLine($incoming);
        $existing = rtrim($existing);
        if ($existing === '') {
            return $this->capMessage($line);
        }
        $lastLine = strrchr($existing, "\n");
        $lastLine = $lastLine === false ? $existing : substr($lastLine, 1);
        if ($this->stripLogTimestamp($lastLine) === $this->stripLogTimestamp($line)) {
            return $existing;
        }

        return $this->capMessage($existing . "\n" . $line);
    }

    private function formatLogLine(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '';
        }
        if (preg_match('/^\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}\]/', $message) === 1) {
            return $message;
        }

        return '[' . date('Y-m-d H:i:s') . '] ' . $message;
    }

    private function stripLogTimestamp(string $line): string
    {
        return (string) preg_replace('/^\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}\]\s*/', '', trim($line));
    }

    private function capMessage(string $message): string
    {
        if (strlen($message) <= self::MESSAGE_MAX_BYTES) {
            return $message;
        }

        return "...[truncated]...\n" . substr($message, -(self::MESSAGE_MAX_BYTES - 20));
    }

    private function leaseDuration(): int
    {
        return ExecutionLeaseConfig::duration();
    }

    private function affected(mixed $n): bool
    {
        return $n === true || (int) $n === 1;
    }
}
