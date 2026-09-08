<?php

declare(strict_types=1);

namespace App\Module\Cron;

use App\Module\Cron\Exception\CronTaskException;

/**
 * Execution Lease 相关环境变量。心跳间隔不单独配置，由 duration 推导。
 */
final class ExecutionLeaseConfig
{
    public const DURATION_DEFAULT = 60;

    public const DURATION_MIN = 20;

    public const RECOVERY_DEFAULT = 30;

    public const GRACE_DEFAULT = 10;

    /**
     * 未设置默认 60 秒；显式设置且 &lt; 20 抛异常。
     */
    public static function duration(): int
    {
        $raw = env('EXECUTION_LEASE_DURATION');
        if ($raw === null || $raw === '') {
            return self::DURATION_DEFAULT;
        }
        $seconds = (int) $raw;
        if ($seconds < self::DURATION_MIN) {
            throw CronTaskException::throw(
                'EXECUTION_LEASE_DURATION 不能小于 ' . self::DURATION_MIN . ' 秒，当前=' . $seconds,
            );
        }

        return $seconds;
    }

    /**
     * 续租间隔（秒）= (duration / 2) - 5。
     */
    public static function heartbeatInterval(): int
    {
        return max(1, intdiv(self::duration(), 2) - 5);
    }

    /**
     * 未设置默认 30 秒。
     */
    public static function recoveryInterval(): int
    {
        $raw = env('EXECUTION_LEASE_RECOVERY_INTERVAL');
        if ($raw === null || $raw === '') {
            return self::RECOVERY_DEFAULT;
        }

        return max(1, (int) $raw);
    }

    /**
     * 未设置默认 10 秒。
     */
    public static function terminateGracePeriod(): int
    {
        $raw = env('EXECUTION_TERMINATE_GRACE_PERIOD');
        if ($raw === null || $raw === '') {
            return self::GRACE_DEFAULT;
        }

        return max(1, (int) $raw);
    }
}
