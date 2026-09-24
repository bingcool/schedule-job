<?php

namespace App\Module\Cron\Entity;

use App\Model\BaseModel;

/**
 * @property int id
 * @property int cron_id cron_task.id
 * @property int node_id 操作时的节点ID快照
 * @property string task_name 操作时的任务名称快照
 * @property int action_type 1=启用 2=禁用 3=删除 4=执行 5=编辑 6=取消执行
 * @property int operator_id staff_user.id
 * @property string operator_name 操作人展示名快照
 * @property array|null content_before 变更前任务内容
 * @property array|null content_after 变更后任务内容
 * @property string created_at 操作时间
 */
class CronTaskOperationLogEntity extends BaseModel
{
    protected static $table = 'cron_task_operation_log';

    protected $pk = 'id';

    public function loadById(int $id): ?static
    {
        return $id <= 0 ? null : $this->loadOne(['id' => (int) $id]);
    }

    protected $casts = [
        'content_before' => 'array',
        'content_after' => 'array',
    ];
}
