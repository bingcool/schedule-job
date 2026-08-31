<?php

declare(strict_types=1);

namespace App\Module\Staff\Entity;

use App\Model\ClientModel;
use App\Module\Staff\StaffApp;
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
class StaffMenuPageEntity extends ClientModel
{
    protected static $table = 'staff_menu_pages';

    protected $pk = 'id';

    public static function queryVisible(): Query
    {
        return static::query()
            ->where('status', '<>', StaffApp::MENU_STATUS_DELETED)
            ->whereNull('delete_at');
    }

    public function loadById(int $id): ?static
    {
        return $this->loadOne(['id' => $id]);
    }
}
