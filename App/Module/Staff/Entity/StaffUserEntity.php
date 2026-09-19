<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\ClientModel;
use Swoolefy\Library\Db\Concern\SoftDelete;

/**
 * @property int $id
 * @property string $account
 * @property string|null $email
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
    use SoftDelete;

    protected static $table = 'staff_user';

    protected $pk = 'id';

    public function loadById(int $id): ?static
    {
        return $this->loadOne(['id' => $id]);
    }

    public function loadByAccount(string $account): ?static
    {
        return $this->loadOne(['account' => $account]);
    }

    public function loadByEmail(string $email): ?static
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return $this->loadOne(['email' => $email]);
    }

    /**
     * 邮箱是否已被其他用户占用（email 或 account）。
     */
    public static function findIdUsingEmail(string $email, ?int $exceptId = null): ?int
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        $emailQb = static::query()->where('email', $email);
        $accountQb = static::query()->where('account', $email);
        if ($exceptId !== null && $exceptId > 0) {
            $emailQb->where('id', '<>', $exceptId);
            $accountQb->where('id', '<>', $exceptId);
        }
        $row = $emailQb->field(['id'])->find();
        if ($row) {
            return (int) ($row['id'] ?? 0) ?: null;
        }
        $row = $accountQb->field(['id'])->find();
        if ($row) {
            return (int) ($row['id'] ?? 0) ?: null;
        }

        return null;
    }

    /**
     * 登录标识：含 @ 按邮箱查（email，兼容账号本身是该邮箱）；否则按 account 查。
     */
    public function loadByLoginIdentity(string $identity): ?static
    {
        $identity = trim($identity);
        if ($identity === '') {
            return null;
        }
        if (str_contains($identity, '@')) {
            $user = $this->loadByEmail($identity);
            if ($user !== null) {
                return $user;
            }

            return $this->loadByAccount($identity);
        }

        return $this->loadByAccount($identity);
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
