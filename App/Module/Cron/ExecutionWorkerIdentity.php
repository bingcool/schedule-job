<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * 当前 Cron Worker 进程的 Lease Owner。
 *
 * 格式：node_id:worker_pid:boot_id
 * 进程重启后 boot_id 一定变化，避免旧 Worker 续租已被 Recovery 的 Execution。
 */
final class ExecutionWorkerIdentity
{
    private static ?string $bootId = null;

    public static function nodeId(): int
    {
        return (int) env('CRON_NODE_ID', 0);
    }

    public static function bootId(): string
    {
        if (self::$bootId === null) {
            self::$bootId = bin2hex(random_bytes(8));
        }

        return self::$bootId;
    }

    public static function owner(): string
    {
        $pid = (int) getmypid();

        return self::nodeId() . ':' . $pid . ':' . self::bootId();
    }
}
