<?php
namespace App;
use Swoolefy\Core\Application;
use Swoolefy\Library\Db\Mysql;

class Factory
{
    /**
     * @return Mysql|\Swoolefy\Core\Dto\ContainerObjectDto|bool
     */
    public static function getDb()
    {
        return Application::getApp()->get('db');
    }
}