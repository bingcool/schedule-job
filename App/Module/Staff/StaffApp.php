<?php

declare(strict_types=1);

namespace App\Module\Staff;

/**
 * 当前 Schedule Job 应用在 RBAC 表中的 app_id。
 */
final class StaffApp
{
    public const APP_ID = 1;

    public const PERMISSION_TYPE_API = 1;

    public const PERMISSION_TYPE_TASK = 2;

    public const MENU_STATUS_DISABLED = 0;

    public const MENU_STATUS_ENABLED = 1;

    public const MENU_STATUS_DELETED = 2;

    public static function appId(): int
    {
        $value = env('STAFF_APP_ID', self::APP_ID);

        return max(1, (int) $value);
    }
}
