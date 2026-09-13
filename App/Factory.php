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
        $app = Application::getApp();
        if ($app === null) {
            throw new \RuntimeException('Application context is not ready');
        }

        return $app->get('db');
    }
}