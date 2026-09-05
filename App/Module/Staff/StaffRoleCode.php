<?php

declare(strict_types=1);

namespace App\Module\Staff;

/**
 * 系统内置角色唯一标识（staff_roles.code）。
 */
final class StaffRoleCode
{
    public const SUPER_ADMIN = 'super_admin';

    /** 可编辑他人创建的计划任务（如同事离职后的任务维护和分配给小组长） */
    public const EDITOR_TASK_GROUP = 'editer_task_group';

    public static function isSystem(string $code): bool
    {
        return in_array($code, self::all(), true);
    }

    /** 系统内置角色状态固定为启用，不可禁用。 */
    public static function isStatusLocked(string $code): bool
    {
        return self::isSystem($code);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::EDITOR_TASK_GROUP,
        ];
    }
}
