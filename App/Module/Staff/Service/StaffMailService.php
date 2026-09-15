<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use PHPMailer\PHPMailer\PHPMailer;
use App\Logger\RunLog;

/**
 * 后台通知邮件。当前用于把临时重置密码发到用户邮箱。
 * 通过 phpmailer/phpmailer 走 SMTP；发件人 / SMTP 全部来自 {@see APP_PATH}/Config/mail.php（.env）。
 * 未配 MAIL_FROM 或 MAIL_SMTP_HOST 时不发送，返回 false，不打断重置流程。
 */
class StaffMailService
{
    /**
     * 读取邮件配置。
     *
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $file = APP_PATH . '/Config/mail.php';
        $config = is_file($file) ? include $file : [];

        return is_array($config) ? $config : [];
    }

    /**
     * 发送重置密码邮件。
     * 主题：schedule job 重置密码；正文含明文临时密码，并提示 3 天内有效。
     */
    public function sendResetPassword(string $to, string $password): bool
    {
        $to = strtolower(trim($to));
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

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
        $config = $this->config();
        $from = trim((string) ($config['from'] ?? ''));
        if ($from === '' || filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            $this->log('MAIL_FROM 未配置或不是合法邮箱，跳过发送');

            return false;
        }
        $host = trim((string) ($config['smtp_host'] ?? ''));
        if ($host === '') {
            $this->log('MAIL_SMTP_HOST 未配置，跳过发送');

            return false;
        }

        try {
            $this->sendByPhpMailer($config, $from, $to, $subject, $body);

            return true;
        } catch (\Throwable $e) {
            $this->log('发送重置密码邮件失败: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * 用 PHPMailer SMTP 投递。
     * 465/ssl 用 SMTPS，587/tls 用 STARTTLS。
     * 未配 MAIL_SMTP_USER 时用 MAIL_FROM 做认证账号。
     *
     * @param array<string, mixed> $config
     */
    private function sendByPhpMailer(array $config, string $from, string $to, string $subject, string $body): void
    {
        $host = trim((string) ($config['smtp_host'] ?? ''));
        $port = (int) ($config['smtp_port'] ?? 465);
        $encryption = strtolower((string) ($config['smtp_encryption'] ?? 'ssl'));
        $timeout = (int) ($config['timeout'] ?? 10);
        $user = trim((string) ($config['smtp_user'] ?? ''));
        if ($user === '') {
            $user = $from;
        }
        $password = (string) ($config['smtp_password'] ?? '');
        $fromName = trim((string) ($config['from_name'] ?? 'Schedule Job'));

        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port > 0 ? $port : 465;
        $mail->Timeout = $timeout > 0 ? $timeout : 10;
        $mail->SMTPAuth = $password !== '';
        if ($mail->SMTPAuth) {
            $mail->Username = $user;
            $mail->Password = $password;
        }
        $mail->SMTPSecure = match ($encryption) {
            'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            'none', '' => '',
            default => PHPMailer::ENCRYPTION_SMTPS,
        };
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
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
