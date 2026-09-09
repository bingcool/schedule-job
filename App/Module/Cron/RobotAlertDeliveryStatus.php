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
}
