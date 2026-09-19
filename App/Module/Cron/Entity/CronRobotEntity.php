<?php

declare(strict_types=1);

namespace App\Module\Cron\Entity;

use App\Model\ClientModel;
use Swoolefy\Library\Db\Concern\SoftDelete;

/**
 * @property int $id
 * @property string $name
 * @property int $platform
 * @property string $webhook_url
 * @property string $secret
 * @property array|string|null $config_json
 * @property int $status
 * @property string|null $last_test_at
 * @property int $last_test_ok
 * @property string $last_test_error
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class CronRobotEntity extends ClientModel
{
    use SoftDelete;

    protected static $table = 'cron_robot';

    protected $pk = 'id';

    protected $casts = [
        'config_json' => 'array',
        'platform' => 'int',
        'status' => 'int',
        'last_test_ok' => 'int',
    ];

    public function loadById(int $id): ?static
    {
        return $this->loadOne(['id' => $id]);
    }

    /**
     * 含已软删（withoutTrashed 在本 ORM 中关闭软删过滤）。
     *
     * @return array<string, mixed>|null
     */
    public static function findRowIncludingDeleted(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = static::withoutTrashed()->where('id', $id)->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }
}
