<?php

declare(strict_types=1);

namespace App\Module\Cron\Entity;

use App\Model\BaseModel;
use Swoolefy\Library\Db\Concern\SoftDelete;

/**
 * 手动执行请求表。独立于 cron_task，避免改 flag 触发 updated_at / fingerprint UPDATE。
 * 表有 deleted_at，使用 SoftDelete 后 query() 会自动加 deleted_at IS NULL。
 *
 * @property int $id
 * @property int $cron_id
 * @property string $requested_at
 * @property string|null $consumed_at
 * @property string created_at 创建时间
 * @property string updated_at 修改时间
 * @property string deleted_at 删除时间
 */
class CronTaskRunRequestEntity extends BaseModel
{
    use SoftDelete;

    protected static $table = 'cron_task_run_request';

    protected $pk = 'id';

    public function loadById(int $id): ?static
    {
        return $id <= 0 ? null : $this->loadOne(['id' => $id]);
    }
}
