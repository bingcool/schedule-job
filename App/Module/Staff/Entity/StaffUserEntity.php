<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\ClientModel;
use Swoolefy\Library\Db\Query;

/**
 * @property int $id
 * @property string $account
 * @property string $password
 * @property string $user_name
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $delete_at
 * @property string|null $enabled_at
 * @property string|null $disabled_at
 */
class StaffUserEntity extends ClientModel
{
    protected static $table = 'staff_user';

    protected $pk = 'id';

    public static function queryActive(): Query
    {
        return static::query()->whereNull('delete_at');
    }

    public function loadById(int $id): ?static
    {
        return $this->loadOne(['id' => $id]);
    }

    public function loadByAccount(string $account): ?static
    {
        return $this->loadOne(['account' => $account]);
    }

    public function isDeleted(): bool
    {
        return $this->delete_at !== null && $this->delete_at !== '';
    }

    public function isDisabled(): bool
    {
        return (int) $this->status === 0;
    }
}
