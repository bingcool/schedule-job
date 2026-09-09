<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

use App\Module\Cron\RobotPlatform;

/**
 * 钉钉自定义机器人。
 *
 * @see https://open.dingtalk.com/document/orgapp/custom-bot-send-message-type
 * @see https://open.dingtalk.com/document/orgapp/customize-robot-security-settings
 *
 * 加签：timestamp(ms) + "\n" + secret 作为待签串，HMAC-SHA256(key=secret) → Base64 → urlEncode，
 * 拼到 URL：timestamp、sign。无 secret 则不加签（依赖关键词/IP 白名单）。
 * 告警 markdown.title + text；测试 text。成功 errcode=0。
 */
final class DingTalkRobotStrategy implements RobotStrategyInterface
{
    public function __construct(
        private readonly RobotWebhookClient $client = new RobotWebhookClient(),
    ) {
    }

    public function send(RobotConfig $config, RobotAlertMessage $message): RobotSendResult
    {
        $url = $this->signedUrl($config);
        if ($message->isTest) {
            $payload = [
                'msgtype' => 'text',
                'text' => [
                    'content' => $message->testText(),
                ],
            ];
        } else {
            $payload = [
                'msgtype' => 'markdown',
                'markdown' => [
                    'title' => $message->alertTitle(),
                    'text' => $message->toMarkdown(false),
                ],
            ];
        }

        return $this->client->postJson($url, $payload);
    }

    public static function platform(): int
    {
        return RobotPlatform::DINGTALK;
    }

    private function signedUrl(RobotConfig $config): string
    {
        $url = $config->webhookUrl;
        $secret = $config->secret;
        if ($secret === '') {
            return $url;
        }
        $timestamp = (string) (int) round(microtime(true) * 1000);
        $stringToSign = $timestamp . "\n" . $secret;
        $sign = urlencode(base64_encode(hash_hmac('sha256', $stringToSign, $secret, true)));
        $sep = str_contains($url, '?') ? '&' : '?';

        return $url . $sep . 'timestamp=' . $timestamp . '&sign=' . $sign;
    }
}
