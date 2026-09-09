<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

use App\Module\Cron\RobotPlatform;

/**
 * 飞书自定义机器人。
 *
 * @see https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot
 *
 * 签名校验：timestamp 为秒；以 timestamp+"\n"+secret 为 HMAC key，对空字符串做 HmacSHA256 再 Base64。
 * 放入 JSON 字段 timestamp、sign（不是 query）。无 secret 则不签名。
 * 告警 msg_type=post；测试 msg_type=text。成功 code=0。
 */
final class FeishuRobotStrategy implements RobotStrategyInterface
{
    public function __construct(
        private readonly RobotWebhookClient $client = new RobotWebhookClient(),
    ) {
    }

    public function send(RobotConfig $config, RobotAlertMessage $message): RobotSendResult
    {
        if ($message->isTest) {
            $payload = [
                'msg_type' => 'text',
                'content' => [
                    'text' => $message->testText(),
                ],
            ];
        } else {
            $payload = [
                'msg_type' => 'post',
                'content' => [
                    'post' => $message->toFeishuPost(),
                ],
            ];
        }

        return $this->client->postJson($config->webhookUrl, $this->withSign($config, $payload));
    }

    public static function platform(): int
    {
        return RobotPlatform::FEISHU;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withSign(RobotConfig $config, array $payload): array
    {
        $secret = $config->secret;
        if ($secret === '') {
            return $payload;
        }
        $timestamp = (string) time();
        $stringToSign = $timestamp . "\n" . $secret;
        $sign = base64_encode(hash_hmac('sha256', '', $stringToSign, true));
        $payload['timestamp'] = $timestamp;
        $payload['sign'] = $sign;

        return $payload;
    }
}
