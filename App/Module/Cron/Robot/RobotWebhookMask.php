<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

/**
 * Webhook URL / 错误文案脱敏。禁止把完整 query、secret 写入 API 或 alert_log。
 */
final class RobotWebhookMask
{
    public static function maskUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return '******';
        }
        $scheme = $parts['scheme'] ?? 'https';
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        foreach (array_keys($query) as $key) {
            $query[$key] = '******';
        }
        $qs = $query !== [] ? '?' . http_build_query($query) : '';

        return $scheme . '://' . $host . $port . $path . $qs;
    }

    public static function sanitizeError(string $text, string $webhookUrl = '', string $secret = ''): string
    {
        $text = trim($text);
        if ($webhookUrl !== '') {
            $text = str_replace($webhookUrl, '[webhook]', $text);
        }
        if ($secret !== '') {
            $text = str_replace($secret, '[secret]', $text);
        }
        $text = (string) preg_replace('#https?://[^\s\'"\\\\]+#i', '[url]', $text);
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 1000);
        }

        return substr($text, 0, 1000);
    }
}
