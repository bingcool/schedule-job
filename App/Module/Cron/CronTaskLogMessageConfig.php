<?php

declare(strict_types=1);

namespace App\Module\Cron;

use App\Module\Cron\Exception\CronTaskException;

/**
 * cron_task_log.message 追加长度。硬顶 60000，避免撑破 MySQL TEXT。
 */
final class CronTaskLogMessageConfig
{
    public const DEFAULT_BYTES = 30000;

    public const HARD_MAX_BYTES = 60000;

    public static function maxBytes(): int
    {
        $raw = env('CRON_TASK_LOG_MESSAGE_MAX_BYTES');
        if ($raw === null || $raw === '') {
            return self::DEFAULT_BYTES;
        }
        $bytes = (int) $raw;
        if ($bytes <= 0) {
            throw CronTaskException::throw(
                'CRON_TASK_LOG_MESSAGE_MAX_BYTES 必须大于 0，当前=' . $bytes,
            );
        }
        if ($bytes > self::HARD_MAX_BYTES) {
            throw CronTaskException::throw(
                'CRON_TASK_LOG_MESSAGE_MAX_BYTES 不能超过 ' . self::HARD_MAX_BYTES . ' 字节，当前=' . $bytes,
            );
        }

        return $bytes;
    }
}
