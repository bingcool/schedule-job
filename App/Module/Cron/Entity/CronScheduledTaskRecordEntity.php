<?php

declare(strict_types=1);

namespace App\Module\Cron\Entity;

use App\Model\BaseModel;

/**
 * 调度 Slot 抢占记录。UNIQUE(cron_id, scheduled_at) 保证同一调度点只有一个赢家。
 *
 * execution_id=0 表示已占坑尚未绑定 Execution；赢家 RUNNING 日志落库后回写。
 * RunOnce 不写本表。
 *
 * @property int $id
 * @property int $cron_id
 * @property string $scheduled_at
 * @property int $execution_id
 * @property string $created_at
 * @property string $updated_at
 * @see \Swoolefy\Worker\Cron\CronScheduleSlotClaimConst
 */
class CronScheduledTaskRecordEntity extends BaseModel
{

    protected static $table = 'cron_scheduled_task_record';

    protected $pk = 'id';

    public function loadById(int $id): ?static
    {
        return $id <= 0 ? null : $this->loadOne(['id' => $id]);
    }

    protected $casts = [
        'cron_id' => 'int',
        'execution_id' => 'int',
    ];
}
