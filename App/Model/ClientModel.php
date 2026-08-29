<?php
namespace App\Model;

use App\Factory;
use Swoolefy\Library\Db\Model;

class ClientModel extends Model {
    /**
     * @var int
     */
    protected $userId;

    /**
     * @inheritDoc
     */
    public function getConnection()
    {
        if (is_object($this->connection)) {
            return $this->connection;
        }
        // 通过query获取user对应所在的dbId
        return Factory::getDb();
    }

    /**
     * return null：数据库自增
     * @return int|mixed
     */
    public function createPkValue()
    {
        return null;
    }
}