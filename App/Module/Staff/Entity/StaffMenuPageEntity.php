<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\BaseModel;
use App\Module\Staff\StaffApp;
use Swoolefy\Library\Db\Concern\SoftDelete;
use Swoolefy\Library\Db\Query;

/**
 * @property int $id
 * @property int $app_id
 * @property string $name
 * @property string $parent_prefix
 * @property int $parent_id
 * @property string $uri
 * @property string $code
 * @property string $icon
 * @property int $sort
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $delete_at
 */
class StaffMenuPageEntity extends BaseModel
{
    use SoftDelete;

    protected static $table = 'staff_menu_pages';

    protected $pk = 'id';

    /**
     * 未软删且未标删除态的菜单。delete_at 由 SoftDelete 在 query() 上自动过滤。
     */
    public static function queryVisible(): Query
    {
        return static::query()->where('status', '<>', StaffApp::MENU_STATUS_DELETED);
    }

    public function loadById(int $id): ?static
    {
        return $this->loadOne(['id' => $id]);
    }
}
