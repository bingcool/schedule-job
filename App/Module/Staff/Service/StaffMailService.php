<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use App\Factory;
use App\Logger\RunLog;
use Swoolefy\Library\Mail\MailMessage;

/**
 * 后台通知邮件。当前用于把临时重置密码发到用户邮箱。
 * 通过 library 的 Mail 组件发送，发件人 / SMTP 来自 {@see APP_PATH}/Config/mail.php（.env）；
 * 未配置或投递失败时不发送，返回 false，不打断重置流程。
 */
class StaffMailService
{
    /**
     * 发送重置密码邮件。
     * 主题：schedule job 重置密码；正文含明文临时密码，并提示 3 天内有效。
     */
    public function sendResetPassword(string $to, string $password): bool
    {
        $ttlDays = ResetPasswordTokenService::TTL_DAYS;
        $subject = 'schedule job 重置密码';
        $body = "您好，\n\n"
            . "您的 Schedule Job 账号已重置密码：\n\n"
            . $password . "\n\n"
            . "该重置密码 {$ttlDays} 天内有效，登录后请尽快修改密码。\n";

        return $this->send($to, $subject, $body);
    }

    /**
     * 通用发送入口。配置不齐或 SMTP 失败只记日志，返回 false。
     */
    public function send(string $to, string $subject, string $body): bool
    {
        $to = strtolower(trim($to));
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        try {
            Factory::getMailer()->send(
                (new MailMessage())
                    ->to($to)
                    ->subject($subject)
                    ->text($body),
            );

            return true;
        } catch (\Throwable $e) {
            $this->log('发送邮件失败: ' . $e->getMessage());

            return false;
        }
    }

    private function log(string $message): void
    {
        $msg = '[staff-mail] ' . $message;
        try {
            RunLog::error($msg);
            return;
        } catch (\Throwable) {
        }
        error_log($msg);
    }
}
