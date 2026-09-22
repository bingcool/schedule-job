<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\ClientModel;

/**
 * @property int $id
 * @property int $user_id
 * @property int $node_group_id
 * @property string $created_at
 * @property string $updated_at
 */
class StaffUserRelateNodeGroupEntity extends ClientModel
{
    protected static $table = 'staff_user_relate_node_group';

    protected $pk = 'id';

    public function loadById(int $id): ?static
    {
        return $id <= 0 ? null : $this->loadOne(['id' => $id]);
    }
}
