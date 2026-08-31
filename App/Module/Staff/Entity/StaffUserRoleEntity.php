<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\ClientModel;

/**
 * @property int $app_id
 * @property int $user_id
 * @property int $role_id
 * @property string $created_at
 * @property string $updated_at
 */
class StaffUserRoleEntity extends ClientModel
{
    protected static $table = 'staff_user_role';
}
