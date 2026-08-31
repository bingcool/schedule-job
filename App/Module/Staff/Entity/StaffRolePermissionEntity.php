<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\ClientModel;

/**
 * @property int $id
 * @property int $app_id
 * @property int $type
 * @property int $role_id
 * @property int $per_id
 * @property string $created_at
 * @property string $updated_at
 */
class StaffRolePermissionEntity extends ClientModel
{
    protected static $table = 'staff_role_permission';

    protected $pk = 'id';
}
