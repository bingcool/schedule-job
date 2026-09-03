<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * cron_task_operation_log.action_type
 */
final class CronTaskOperationType
{
    public const ENABLE = 1;

    public const DISABLE = 2;

    public const DELETE = 3;

    public const RUN = 4;

    public const EDIT = 5;

    public static function label(int $type): string
    {
        return match ($type) {
            self::ENABLE => '启用任务',
            self::DISABLE => '禁用任务',
            self::DELETE => '删除任务',
            self::RUN => '执行任务',
            self::EDIT => '编辑任务',
            default => '未知操作',
        };
    }

    public static function isValid(?int $type): bool
    {
        return in_array($type, [self::ENABLE, self::DISABLE, self::DELETE, self::RUN, self::EDIT], true);
    }

    /**
     * @return list<int>
     */
    public static function all(): array
    {
        return [self::ENABLE, self::DISABLE, self::DELETE, self::RUN, self::EDIT];
    }
}
