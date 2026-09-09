<?php

declare(strict_types=1);

namespace App\Module\Cron\Entity;

use App\Model\ClientModel;

/**
 * @property int $id
 * @property int $execution_id
 * @property int $cron_id
 * @property string $exec_batch_id
 * @property int $robot_id
 * @property int $platform
 * @property int $alert_type
 * @property int $status
 * @property string $skip_reason
 * @property int $http_status
 * @property string $error_message
 * @property string|null $sent_at
 * @property string $created_at
 * @property string $updated_at
 */
class CronRobotAlertLogEntity extends ClientModel
{
    protected static $table = 'cron_robot_alert_log';

    protected $pk = 'id';

    protected $casts = [
        'execution_id' => 'int',
        'cron_id' => 'int',
        'robot_id' => 'int',
        'platform' => 'int',
        'alert_type' => 'int',
        'status' => 'int',
        'http_status' => 'int',
    ];
}
