<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\ExecutionLeaseConfig;
use App\Module\Cron\ExecutionWorkerIdentity;
use App\Module\Cron\FailureReason;
use Swoole\Coroutine\Channel;
use Swoolefy\Core\BaseServer;
use Swoolefy\Worker\Cron\ExecutionStatus;

/**
 * 当前 Worker 进程内的 Execution 看护：心跳续租、超时 SIGTERM/SIGKILL、响应 Cancel。
 *
 * 心跳必须与执行协程同进程；Tick 在独立 Timer 回调里跑，不阻塞 proc_open wait。
 *
 * ## 两种「可杀句柄」
 *
 * - **PID**：Shell 任务（exec_type=1）。SIGTERM → grace → SIGKILL。
 * - **terminator 回调**：进程不在本机时用（exec_type=3 的 Kubernetes Job）。
 *   由执行器在资源创建成功后通过 {@see attachTerminator} 注册。
 *
 * 没有这个回调时，pid=0 的执行在收到取消请求后只会被标成 CANCELLED，而集群里的
 * Job 仍在跑 —— 也就是孤儿资源。HTTP（exec_type=2）不注册回调，维持原有行为：
 * 请求终会自己结束，影响可控。
 */
final class ExecutionRuntimeGuard
{
    /** @var array<int, array{pid:int,timeoutAt:?string,termSentAt:int,killSentAt:int,terminator:?callable}> */
    private static array $watched = [];

    /** @var Channel|int|null */
    private static Channel|int|null $timerHandle = null;

    private static int $lastRecoveryAt = 0;

    private static int $lastHeartbeatAt = 0;

    public static function boot(): void
    {
        ExecutionLeaseConfig::duration();
        self::ensureTimer();
        (new ExecutionService())->recoverExpiredLeases();
    }

    public static function watch(int $logId, int $pid = 0, ?string $timeoutAt = null): void
    {
        if ($logId <= 0) {
            return;
        }
        $prev = self::$watched[$logId] ?? null;
        self::$watched[$logId] = [
            'pid' => $pid > 0 ? $pid : (int) ($prev['pid'] ?? 0),
            'timeoutAt' => $timeoutAt ?: ($prev['timeoutAt'] ?? null),
            'termSentAt' => (int) ($prev['termSentAt'] ?? 0),
            'killSentAt' => (int) ($prev['killSentAt'] ?? 0),
            'terminator' => $prev['terminator'] ?? null,
        ];
        self::ensureTimer();
    }

    /**
     * 注册「没有 PID 时如何停止这次执行」。
     *
     * 典型调用方：{@see \App\Module\Cron\Kubernetes\KubernetesExecutionHook}，
     * 在 Job 创建成功后把「删除该 Job」交给 Guard。回调应当**幂等**（重复删除不报错），
     * 因为 Guard 与执行器可能各删一次。
     *
     * 必须在 attempt 结束时调用 {@see detachTerminator} 解除，否则重试进入下一个
     * attempt 后，Guard 手里还攥着上一次的资源句柄。
     *
     * @param callable $terminator `fn(): bool`；返回值只用于日志
     */
    public static function attachTerminator(int $logId, callable $terminator): void
    {
        if ($logId <= 0) {
            return;
        }
        if (!isset(self::$watched[$logId])) {
            self::watch($logId);
        }
        self::$watched[$logId]['terminator'] = $terminator;
    }

    /**
     * 解除外部资源句柄。执行已经结束时调用，避免 Guard 去删下一次尝试的资源。
     */
    public static function detachTerminator(int $logId): void
    {
        if (isset(self::$watched[$logId])) {
            self::$watched[$logId]['terminator'] = null;
        }
    }

    public static function touchPid(int $logId, int $pid): void
    {
        if ($logId <= 0 || $pid <= 0) {
            return;
        }
        if (!isset(self::$watched[$logId])) {
            self::$watched[$logId] = [
                'pid' => $pid,
                'timeoutAt' => null,
                'termSentAt' => 0,
                'killSentAt' => 0,
                'terminator' => null,
            ];
        } else {
            self::$watched[$logId]['pid'] = $pid;
        }
        self::ensureTimer();
    }

    public static function unwatch(int $logId): void
    {
        unset(self::$watched[$logId]);
    }

    public static function signalPid(int $pid, int $signal): bool
    {
        if ($pid <= 1) {
            return false;
        }
        try {
            if (class_exists(\Swoole\Process::class)) {
                return (bool) \Swoole\Process::kill($pid, $signal);
            }
            if (function_exists('posix_kill')) {
                return (bool) posix_kill($pid, $signal);
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private static function ensureTimer(): void
    {
        if (self::$timerHandle !== null) {
            return;
        }
        if (!function_exists('goTick')) {
            return;
        }
        try {
            // goTick 会走 EventApp，Timer 回调才能拿到 db 容器。
            // 阻塞不重叠：心跳 / 回收 / 杀进程不能并发跑两轮。
            $handle = goTick(5000, static function (): void {
                try {
                    self::onTick();
                } catch (\Throwable $e) {
                    BaseServer::catchException($e);
                }
            }, true);
            if ($handle === false || $handle === 0) {
                self::$timerHandle = null;
                return;
            }
            self::$timerHandle = $handle;
        } catch (\Throwable) {
            self::$timerHandle = null;
        }
    }

    private static function onTick(): void
    {
        $service = new ExecutionService();
        $now = time();
        $owner = ExecutionWorkerIdentity::owner();
        $grace = ExecutionLeaseConfig::terminateGracePeriod();
        $heartbeatEvery = ExecutionLeaseConfig::heartbeatInterval();
        $doHeartbeat = ($now - self::$lastHeartbeatAt) >= $heartbeatEvery;
        if ($doHeartbeat) {
            self::$lastHeartbeatAt = $now;
        }

        // Heartbeat 先于 Recovery，降低本 Worker 因事件循环延迟先把自己判过期的概率。
        // 正确性边界仍是 Lease Snapshot CAS，不是这个顺序。
        foreach (self::$watched as $logId => $state) {
            $row = $service->findById($logId);
            if ($row === null) {
                self::unwatch($logId);
                continue;
            }
            if ((string) ($row['lease_owner'] ?? '') !== $owner) {
                self::unwatch($logId);
                continue;
            }
            $status = (int) ($row['status'] ?? 0);
            if ($doHeartbeat && in_array($status, [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED], true)) {
                $service->heartbeat($logId, $owner);
            }
        }

        $recoveryEvery = ExecutionLeaseConfig::recoveryInterval();
        if ($now - self::$lastRecoveryAt >= $recoveryEvery) {
            self::$lastRecoveryAt = $now;
            $service->recoverExpiredLeases();
        }

        foreach (self::$watched as $logId => $state) {
            $row = $service->findById($logId);
            if ($row === null) {
                self::unwatch($logId);
                continue;
            }
            $status = (int) ($row['status'] ?? 0);
            $pid = (int) ($row['pid'] ?? 0);
            if ($pid > 0) {
                $state['pid'] = $pid;
            } else {
                $pid = (int) $state['pid'];
            }
            $timeoutAt = (string) ($row['timeout_at'] ?? ($state['timeoutAt'] ?? ''));
            if ($timeoutAt !== '') {
                $state['timeoutAt'] = $timeoutAt;
            }
            $timedOut = $timeoutAt !== '' && strtotime($timeoutAt) !== false && strtotime($timeoutAt) <= $now;
            $stopping = $status === ExecutionStatus::CANCEL_REQUESTED
                || $status === ExecutionStatus::CANCELLED
                || $status === ExecutionStatus::TIMEOUT
                || $timedOut
                || $state['termSentAt'] > 0
                || $state['killSentAt'] > 0;

            if ((string) ($row['lease_owner'] ?? '') !== $owner) {
                self::unwatch($logId);
                continue;
            }

            // 有 terminator 时也要接管：否则 pid=0 的 Kubernetes 任务超时后 Guard 什么都不做，
            // 集群里的 Job 会一直跑到 activeDeadlineSeconds 才被 K8s 兜底杀掉
            $terminator = $state['terminator'] ?? null;
            if ($stopping && ($pid > 0 || $terminator !== null || $status === ExecutionStatus::CANCEL_REQUESTED)) {
                $final = ExecutionStatus::TIMEOUT;
                $reason = FailureReason::TIMEOUT;
                if ($status === ExecutionStatus::CANCEL_REQUESTED || $status === ExecutionStatus::CANCELLED) {
                    $final = ExecutionStatus::CANCELLED;
                    $reason = FailureReason::CANCELLED;
                }
                self::terminate($service, $logId, $state, $pid, $grace, $final, $reason);
                if (isset(self::$watched[$logId])) {
                    self::$watched[$logId] = $state;
                }
                continue;
            }

            if (!in_array($status, [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED], true)) {
                self::unwatch($logId);
            }
        }
    }

    /**
     * @param array{pid:int,timeoutAt:?string,termSentAt:int,killSentAt:int,terminator:?callable} $state
     */
    private static function terminate(
        ExecutionService $service,
        int $logId,
        array &$state,
        int $pid,
        int $grace,
        int $finalStatus,
        string $reason,
    ): void {
        $now = time();
        if ($pid <= 0) {
            // 先停外部资源再落终态：反过来会在「Execution 已 CANCELLED」和
            // 「Job 还在跑」之间留下一个窗口，运维看到的状态是错的
            $terminated = self::runTerminator($service, $logId, $state);
            $service->finishOwned(
                $logId,
                ExecutionWorkerIdentity::owner(),
                $finalStatus,
                $reason,
                self::noPidMessage($reason, $terminated),
            );
            self::unwatch($logId);

            return;
        }
        $service->finishOwned(
            $logId,
            ExecutionWorkerIdentity::owner(),
            $finalStatus,
            $reason,
            $reason === FailureReason::CANCELLED
                ? '收到取消请求，准备终止进程'
                : '执行超时，准备终止进程',
        );
        if ($state['termSentAt'] <= 0) {
            self::signalPid($pid, 15);
            $state['termSentAt'] = $now;
            $service->appendLog($logId, '发送 SIGTERM pid=' . $pid);

            return;
        }
        if ($now - $state['termSentAt'] < $grace) {
            if (!self::pidAlive($pid)) {
                self::unwatch($logId);
            }

            return;
        }
        if ($state['killSentAt'] <= 0) {
            self::signalPid($pid, 9);
            $state['killSentAt'] = $now;
            $service->appendLog($logId, 'grace 到期，发送 SIGKILL pid=' . $pid);

            return;
        }
        self::unwatch($logId);
    }

    /**
     * 调用外部资源终止回调。
     *
     * 只调一次：调用后立即置空，避免 tick 反复删同一个资源。回调失败不阻止后续收尾——
     * Execution 的结论已经确定，把它改成 FAILED 只会让语义更糟；残留资源由
     * Job TTL / activeDeadlineSeconds 兜底。
     *
     * @param array{pid:int,timeoutAt:?string,termSentAt:int,killSentAt:int,terminator:?callable} $state
     * @return bool|null null=没有注册回调
     */
    private static function runTerminator(ExecutionService $service, int $logId, array &$state): ?bool
    {
        $terminator = $state['terminator'] ?? null;
        if ($terminator === null) {
            return null;
        }
        $state['terminator'] = null;
        if (isset(self::$watched[$logId])) {
            self::$watched[$logId]['terminator'] = null;
        }

        try {
            $ok = (bool) $terminator();
            $service->appendLog($logId, $ok ? '已请求终止外部执行资源' : '终止外部执行资源未成功');

            return $ok;
        } catch (\Throwable $e) {
            $service->appendLog($logId, '终止外部执行资源异常: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * pid=0 场景的收尾说明，区分「真的把外部资源停了」与「只能标记状态」。
     */
    private static function noPidMessage(string $reason, ?bool $terminated): string
    {
        $action = $reason === FailureReason::CANCELLED ? '收到取消请求' : '执行超时';

        return match ($terminated) {
            true => $action . '，已终止外部执行资源',
            false => $action . '，终止外部执行资源失败，请人工确认',
            default => $action . '（无 PID 可杀）',
        };
    }

    private static function pidAlive(int $pid): bool
    {
        if ($pid <= 1) {
            return false;
        }
        try {
            if (class_exists(\Swoole\Process::class)) {
                return (bool) \Swoole\Process::kill($pid, 0);
            }
            if (function_exists('posix_kill')) {
                return posix_kill($pid, 0);
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
}
