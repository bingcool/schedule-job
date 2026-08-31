<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\ClientModel;

/**
 * @property int $id
 * @property int $app_id
 * @property int $is_super_role
 * @property string $name
 * @property string $code
 * @property string $desc
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 */
class StaffRoleEntity extends ClientModel
{
    protected static $table = 'staff_roles';

    protected $pk = 'id';

    public function loadById(int $id): ?static
    {
        return $this->loadOne(['id' => $id]);
    }

    public function loadByCode(string $code): ?static
    {
        return $this->loadOne(['code' => $code]);
    }

    public function isSuperRole(): bool
    {
        return (int) $this->is_super_role === 1;
    }
}
