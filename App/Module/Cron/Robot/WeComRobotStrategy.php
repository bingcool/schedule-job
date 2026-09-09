<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

use App\Module\Cron\RobotPlatform;

/**
 * 企业微信群机器人 Webhook。
 *
 * @see https://developer.work.weixin.qq.com/document/path/91770
 *
 * POST JSON 至 webhook（URL 含 key，无需加签）。告警用 markdown；连通测试用 text。
 * 成功：{"errcode":0,"errmsg":"ok"}。content 最长 4096 字节。
 */
final class WeComRobotStrategy implements RobotStrategyInterface
{
    public function __construct(
        private readonly RobotWebhookClient $client = new RobotWebhookClient(),
    ) {
    }

    public function send(RobotConfig $config, RobotAlertMessage $message): RobotSendResult
    {
        if ($message->isTest) {
            $payload = [
                'msgtype' => 'text',
                'text' => [
                    'content' => $this->clip($message->testText(), 2048),
                ],
            ];
        } else {
            $payload = [
                'msgtype' => 'markdown',
                'markdown' => [
                    'content' => $this->clip($message->toMarkdown(true), 4096),
                ],
            ];
        }

        return $this->client->postJson($config->webhookUrl, $payload);
    }

    public static function platform(): int
    {
        return RobotPlatform::WECOM;
    }

    private function clip(string $text, int $maxBytes): string
    {
        if (strlen($text) <= $maxBytes) {
            return $text;
        }
        if (function_exists('mb_strcut')) {
            return mb_strcut($text, 0, $maxBytes - 3, 'UTF-8') . '...';
        }

        return substr($text, 0, $maxBytes - 3) . '...';
    }
}
