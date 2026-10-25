<?php

/**
 * 重置密码通知邮件配置，全部从 .env 读取。
 * 由 bingcool/library 的 Mail 组件消费，见 App/Config/component/mail.php。
 *
 * MAIL_FROM           发件人邮箱（必填，否则不发信）
 * MAIL_FROM_NAME      发件人显示名
 * MAIL_SMTP_HOST      SMTP 主机（必填）
 * MAIL_SMTP_PORT      默认 465
 * MAIL_SMTP_USER      认证账号，空则用 MAIL_FROM
 * MAIL_SMTP_PASSWORD  认证密码，空则不启用 SMTP 认证
 * MAIL_SMTP_ENCRYPTION ssl | tls | 空（不加密）
 * MAIL_TIMEOUT        连接 + 读超时秒数
 */

$from = trim((string) env('MAIL_FROM', ''));
$user = trim((string) env('MAIL_SMTP_USER', ''));
$password = (string) env('MAIL_SMTP_PASSWORD', '');

return [
    'mail' => [
        'default_mailer' => 'smtp',
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => trim((string) env('MAIL_SMTP_HOST', '')),
                'port' => (int) env('MAIL_SMTP_PORT', 465),
                'encryption' => strtolower((string) env('MAIL_SMTP_ENCRYPTION', 'ssl')),
                'username' => $user !== '' ? $user : $from,
                'password' => $password,
                'timeout' => (int) env('MAIL_TIMEOUT', 10),
                'auth' => $password !== '',
                'from' => [
                    'address' => $from,
                    'name' => trim((string) env('MAIL_FROM_NAME', 'Schedule Job')),
                ],
            ],
        ],
    ],
];
