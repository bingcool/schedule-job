<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * cron_robot_alert_log.alert_type
 */
final class RobotAlertType
{
    public const FAILED = 1;

    public const TIMEOUT = 2;

    /** Agent 心跳超时，节点判定失联。 */
    public const NODE_OFFLINE = 3;

    /** Agent 心跳恢复或首次上线。 */
    public const NODE_ONLINE = 4;

    public static function isNodeLiveness(int $alertType): bool
    {
        return $alertType === self::NODE_OFFLINE || $alertType === self::NODE_ONLINE;
    }

    public static function isValid(int $alertType): bool
    {
        return in_array($alertType, [self::FAILED, self::TIMEOUT, self::NODE_OFFLINE, self::NODE_ONLINE], true);
    }

    public static function name(int $alertType): string
    {
        return match ($alertType) {
            self::FAILED => '任务失败',
            self::TIMEOUT => '任务超时',
            self::NODE_OFFLINE => '节点失联',
            self::NODE_ONLINE => '节点上线',
            default => '未知',
        };
    }
}
