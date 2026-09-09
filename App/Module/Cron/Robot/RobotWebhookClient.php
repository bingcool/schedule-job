<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

use App\Module\Cron\RobotAlertConfig;

/**
 * 带连接/请求超时的 JSON POST。不跟随跳转，降低 SSRF 面。
 */
final class RobotWebhookClient
{
    /**
     * @param array<string, mixed> $payload
     */
    public function postJson(string $url, array $payload): RobotSendResult
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return RobotSendResult::fail(0, '消息序列化失败');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return RobotSendResult::fail(0, '无法初始化 HTTP 客户端');
        }

        $connect = RobotAlertConfig::connectTimeout();
        $request = RobotAlertConfig::requestTimeout();
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connect,
            CURLOPT_TIMEOUT => $request,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if (defined('CURLPROTO_HTTPS')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            $msg = $errno === CURLE_OPERATION_TIMEDOUT ? 'Webhook 请求超时' : 'Webhook 请求失败';
            if ($err !== '' && !str_contains($err, 'http')) {
                $msg .= '：' . $err;
            }

            return RobotSendResult::fail($httpStatus, $msg);
        }

        $decoded = [];
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $decoded = $json;
            }
        }

        return $this->interpret($httpStatus, $decoded, is_string($raw) ? $raw : '');
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function interpret(int $httpStatus, array $decoded, string $raw): RobotSendResult
    {
        if ($httpStatus >= 400) {
            return RobotSendResult::fail($httpStatus, 'Webhook HTTP ' . $httpStatus);
        }

        $errcode = $decoded['errcode'] ?? $decoded['code'] ?? $decoded['StatusCode'] ?? null;
        if ($errcode !== null && (int) $errcode !== 0) {
            $msg = (string) ($decoded['errmsg'] ?? $decoded['msg'] ?? $decoded['message'] ?? '业务失败');

            return RobotSendResult::fail($httpStatus > 0 ? $httpStatus : 200, $this->clip($msg));
        }

        if ($decoded === [] && $raw !== '' && $httpStatus >= 200 && $httpStatus < 300) {
            return RobotSendResult::success($httpStatus);
        }
        if ($httpStatus >= 200 && $httpStatus < 300) {
            return RobotSendResult::success($httpStatus > 0 ? $httpStatus : 200);
        }
        if ($httpStatus === 0 && $decoded !== []) {
            return RobotSendResult::success(200);
        }

        return RobotSendResult::fail($httpStatus, 'Webhook 响应无法解析');
    }

    private function clip(string $msg): string
    {
        $msg = trim($msg);
        if (function_exists('mb_substr')) {
            return mb_substr($msg, 0, 200);
        }

        return substr($msg, 0, 200);
    }
}
