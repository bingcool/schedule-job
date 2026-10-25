<?php

declare(strict_types=1);

use Swoolefy\Library\Mail\MailerManager;

$config = include APP_PATH . '/Config/mail.php';

return [
    // 按协程缓存的发信入口；SMTP 连接只在 send() 时建立。
    'mail' => static function () use($config): MailerManager {
        return new MailerManager(is_array($config) ? $config : []);
    },
];
