<?php
declare(strict_types=1);
namespace App;
use Swoolefy\Core\Application;
use Swoolefy\Core\Dto\ContainerObjectDto;
use Swoolefy\Library\Db\Mysql;
use Swoolefy\Library\Mail\MailerManager;
use Swoolefy\Support\Auth\JwtAuthGuard;


class Factory
{
    /**
     * @return Mysql|ContainerObjectDto|bool
     */
    public static function getDb()
    {
        return self::app()->get('db');
    }

    /**
     * @return JwtAuthGuard|ContainerObjectDto
     */
    public static function getGuard()
    {
        $guard = self::app()->get('auth.guard');
        if ($guard instanceof ContainerObjectDto) {
            $guard = $guard->getObject();
        }
        if (!$guard instanceof JwtAuthGuard) {
            throw new \RuntimeException('auth.guard is not a JwtAuthGuard');
        }

        return $guard;
    }

    /**
     * 当前协程的邮件发送入口。
     */
    public static function getMailer(): MailerManager
    {
        $mailer = self::app()->get('mail');
        return $mailer;
    }

    private static function app(): object
    {
        $app = Application::getApp();
        if ($app === null) {
            throw new \RuntimeException('Application context is not ready');
        }

        return $app;
    }
}