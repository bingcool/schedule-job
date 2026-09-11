<?php

declare(strict_types=1);

namespace App\Module\Cron\Kubernetes;

use App\Module\Cron\Service\ExecutionRuntimeGuard;
use App\Module\Cron\Service\ExecutionService;
use Swoolefy\Worker\Cron\ExecutionSnapshot;
use Swoolefy\Worker\Cron\ExecutionStatus;
use Swoolefy\Worker\Cron\KubernetesExecutionHookInterface;

/**
 * 把 {@see \Swoolefy\Worker\Cron\KubernetesExecutor} 接到 `cron_task_log` 与
 * {@see ExecutionRuntimeGuard} 上（方案 §11.1、§12）。
 *
 * ## 解决的三个问题
 *
 * 1. **Executor 不知道自己是哪条 Execution**。`ExecutionSnapshot` 只有 `exec_batch_id`，
 *    这里用 `(cron_id, exec_batch_id)` 走 `idx_cron_exec_batch` 反查出 `cron_task_log.id`。
 * 2. **崩溃后找不到 Job**。Job 名在 Create **之前**就写进 `task_item`，
 *    「创建成功后立刻宕机」也能靠这条记录找回句柄。
 * 3. **取消会留下孤儿 Job**。Guard 看到 `CANCEL_REQUESTED` 时，pid=0 的执行原本会被
 *    直接标成 CANCELLED 收工，集群里的 Job 却还在跑。这里把「删 Job」注册成
 *    terminator，Guard 会先删再收尾。
 *
 * ## 约定
 *
 * 本类的方法**不抛异常**。落库失败不该改变一次 Kubernetes 执行的结论；
 * Executor 侧虽然也做了兜底捕获，但在这里吞掉语义更清楚。
 *
 * 生命周期与单次 attempt 对齐：`onJobPlanned` 开始、`onJobFinished` 结束，
 * 重试进入下一个 attempt 时会重新走一遍（Job 名带 attempt 后缀，见 §9.2）。
 */
class KubernetesExecutionHook implements KubernetesExecutionHookInterface
{
    private readonly ExecutionService $service;

    /**
     * `exec_batch_id` → `cron_task_log.id` 的短期缓存。
     *
     * stopSignal() 每个轮询周期都会调用，没有缓存就会多一次「按批次查」的查询。
     * 只在本次 attempt 内有效，`onJobFinished` 清掉，不会随 Worker 生命周期无限增长。
     *
     * @var array<string, int>
     */
    private array $executionIds = [];

    public function __construct(?ExecutionService $service = null)
    {
        $this->service = $service ?? new ExecutionService();
    }

    /**
     * Create 之前把 Job 名落库，并返回 `cron_task_log.id`。
     */
    public function onJobPlanned(ExecutionSnapshot $snapshot, string $namespace, string $jobName): int
    {
        $executionId = $this->resolveExecutionId($snapshot, refresh: true);
        if ($executionId <= 0) {
            return 0;
        }

        try {
            $this->service->mergeTaskItemMeta($executionId, [
                'k8s_namespace' => $namespace,
                'k8s_job_name' => $jobName,
                'k8s_attempt' => max(1, $snapshot->attempt),
            ]);
            $this->service->appendLog($executionId, sprintf('准备创建 Kubernetes Job %s/%s', $namespace, $jobName));
        } catch (\Throwable) {
            // 元数据写失败不阻断执行：exec_batch_id 仍可反推 Job 名（§11.3 的兜底路径）
        }

        return $executionId;
    }

    /**
     * Job 已存在于集群：补 UID，并把「删 Job」交给 Guard。
     */
    public function onJobCreated(
        ExecutionSnapshot $snapshot,
        string $namespace,
        string $jobName,
        string $uid,
        callable $terminator,
    ): void {
        $executionId = $this->resolveExecutionId($snapshot);
        if ($executionId <= 0) {
            return;
        }

        try {
            $this->service->mergeTaskItemMeta($executionId, [
                'k8s_job_uid' => $uid,
            ]);
            $this->service->appendLog($executionId, sprintf('Kubernetes Job 已创建 %s/%s uid=%s', $namespace, $jobName, $uid));
            ExecutionRuntimeGuard::attachTerminator($executionId, $terminator);
        } catch (\Throwable) {
        }
    }

    /**
     * 轮询期间判断是否应当停止等待。
     *
     * 取消优先于超时：运维显式点了取消，即便同时也超了时，语义上也应该记成 CANCELLED。
     * TIMEOUT / CANCELLED 这类**已是终态**的情况同样要返回信号——说明 Guard 已经抢先
     * 收尾了，Executor 必须跟着结束，不能继续等一个已经被判死的 Job。
     */
    public function stopSignal(ExecutionSnapshot $snapshot): string
    {
        $executionId = $this->resolveExecutionId($snapshot);
        if ($executionId <= 0) {
            return self::STOP_NONE;
        }

        try {
            $row = $this->service->findById($executionId);
        } catch (\Throwable) {
            // 查不到状态时保持等待，让 Executor 自己的硬上限兜底，避免抖动导致误杀
            return self::STOP_NONE;
        }
        if ($row === null) {
            return self::STOP_NONE;
        }

        $status = (int) ($row['status'] ?? 0);
        if ($status === ExecutionStatus::CANCEL_REQUESTED || $status === ExecutionStatus::CANCELLED) {
            return self::STOP_CANCELLED;
        }
        if ($status === ExecutionStatus::TIMEOUT) {
            return self::STOP_TIMEOUT;
        }

        $timeoutAt = (string) ($row['timeout_at'] ?? '');
        if ($timeoutAt !== '') {
            $deadline = strtotime($timeoutAt);
            if ($deadline !== false && $deadline <= time()) {
                return self::STOP_TIMEOUT;
            }
        }

        return self::STOP_NONE;
    }

    /**
     * attempt 结束：写回 Pod / 镜像等元数据，并解除 Guard 上的删 Job 句柄。
     */
    public function onJobFinished(ExecutionSnapshot $snapshot, array $meta): void
    {
        $executionId = $this->resolveExecutionId($snapshot);
        unset($this->executionIds[$snapshot->execBatchId]);
        if ($executionId <= 0) {
            return;
        }

        try {
            // 重试会进入 attempt 2，Guard 不能再攥着 attempt 1 的 Job 名
            ExecutionRuntimeGuard::detachTerminator($executionId);
            $this->service->mergeTaskItemMeta($executionId, $meta);
        } catch (\Throwable) {
        }
    }

    /**
     * 反查本次执行对应的 `cron_task_log.id`。
     *
     * @param bool $refresh true=跳过缓存重查。onJobPlanned 时 RUNNING 行刚落库，
     *                      必须重查一次拿到真实 id
     */
    protected function resolveExecutionId(ExecutionSnapshot $snapshot, bool $refresh = false): int
    {
        $batchId = $snapshot->execBatchId;
        if (!$refresh && isset($this->executionIds[$batchId])) {
            return $this->executionIds[$batchId];
        }

        try {
            $row = $this->service->findByBatch($snapshot->definition->cronTaskId, $batchId);
        } catch (\Throwable) {
            return 0;
        }

        $executionId = (int) ($row['id'] ?? 0);
        if ($executionId > 0) {
            $this->executionIds[$batchId] = $executionId;
        }

        return $executionId;
    }
}
