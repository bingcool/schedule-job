<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * cron_robot_alert_log.status
 */
final class RobotAlertDeliveryStatus
{
    public const SUCCESS = 1;

    public const FAILED = 2;

    public const SKIPPED = 3;

    public static function isValid(int $status): bool
    {
        return in_array($status, [self::SUCCESS, self::FAILED, self::SKIPPED], true);
    }

    public static function name(int $status): string
    {
        return match ($status) {
            self::SUCCESS => '发送成功',
            self::FAILED => '发送失败',
            self::SKIPPED => '已跳过',
            default => '未知',
        };
    }
}
