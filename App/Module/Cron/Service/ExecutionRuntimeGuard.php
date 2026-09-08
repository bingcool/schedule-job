<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\ExecutionWorkerIdentity;
use App\Module\Cron\FailureReason;
use Swoolefy\Worker\Cron\ExecutionStatus;

/**
 * 当前 Worker 进程内的 Execution 看护：心跳续租、超时 SIGTERM/SIGKILL、响应 Cancel。
 *
 * 心跳必须与执行协程同进程；Tick 在独立 Timer 回调里跑，不阻塞 proc_open wait。
 */
final class ExecutionRuntimeGuard
{
    /** @var array<int, array{pid:int,timeoutAt:?string,termSentAt:int,killSentAt:int}> */
    private static array $watched = [];

    private static int $timerId = 0;

    private static int $lastRecoveryAt = 0;

    public static function boot(): void
    {
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
        ];
        self::ensureTimer();
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
        if (self::$timerId > 0) {
            return;
        }
        if (!class_exists(\Swoole\Timer::class)) {
            return;
        }
        try {
            self::$timerId = (int) \Swoole\Timer::tick(5000, static function (): void {
                self::onTick();
            });
        } catch (\Throwable) {
            self::$timerId = 0;
        }
    }

    private static function onTick(): void
    {
        $service = new ExecutionService();
        $now = time();
        $recoveryEvery = max(10, (int) env('EXECUTION_LEASE_RECOVERY_INTERVAL', 30));
        if ($now - self::$lastRecoveryAt >= $recoveryEvery) {
            self::$lastRecoveryAt = $now;
            $service->recoverExpiredLeases();
        }

        $owner = ExecutionWorkerIdentity::owner();
        $grace = max(1, (int) env('EXECUTION_TERMINATE_GRACE_PERIOD', 10));
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

            if (in_array($status, [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED], true)) {
                $service->heartbeat($logId, $owner);
            }

            if ($stopping && ($pid > 0 || $status === ExecutionStatus::CANCEL_REQUESTED)) {
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
     * @param array{pid:int,timeoutAt:?string,termSentAt:int,killSentAt:int} $state
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
            $service->finishOwned(
                $logId,
                ExecutionWorkerIdentity::owner(),
                $finalStatus,
                $reason,
            );
            self::unwatch($logId);

            return;
        }
        $service->finishOwned(
            $logId,
            ExecutionWorkerIdentity::owner(),
            $finalStatus,
            $reason,
        );
        if ($state['termSentAt'] <= 0) {
            self::signalPid($pid, 15);
            $state['termSentAt'] = $now;

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

            return;
        }
        self::unwatch($logId);
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
