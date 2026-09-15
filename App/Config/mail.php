<?php

/**
 * 重置密码通知邮件配置，全部从 .env 读取。
 *
 * MAIL_FROM           发件人邮箱（必填，否则不发信）
 * MAIL_FROM_NAME      发件人显示名
 * MAIL_SMTP_HOST      SMTP 主机（必填）
 * MAIL_SMTP_PORT      默认 465
 * MAIL_SMTP_USER      认证账号，空则用 MAIL_FROM
 * MAIL_SMTP_PASSWORD  认证密码
 * MAIL_SMTP_ENCRYPTION ssl | tls
 * MAIL_TIMEOUT        连接超时秒数
 */
return [
    'from' => (string) env('MAIL_FROM', ''),
    'from_name' => (string) env('MAIL_FROM_NAME', 'Schedule Job'),
    'smtp_host' => (string) env('MAIL_SMTP_HOST', ''),
    'smtp_port' => (int) env('MAIL_SMTP_PORT', 465),
    'smtp_user' => (string) env('MAIL_SMTP_USER', ''),
    'smtp_password' => (string) env('MAIL_SMTP_PASSWORD', ''),
    'smtp_encryption' => strtolower((string) env('MAIL_SMTP_ENCRYPTION', 'ssl')),
    'timeout' => (int) env('MAIL_TIMEOUT', 10),
];
