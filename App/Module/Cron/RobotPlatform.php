<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * 群机器人平台。与 cron_robot.platform / cron_robot_alert_log.platform 对齐。
 */
final class RobotPlatform
{
    public const WECOM = 1;

    public const DINGTALK = 2;

    public const FEISHU = 3;

    /** @var list<int> */
    public const ALL = [self::WECOM, self::DINGTALK, self::FEISHU];

    /**
     * 各平台官方 Webhook 主机（防 SSRF）。
     *
     * @var array<int, list<string>>
     */
    private const WEBHOOK_HOSTS = [
        self::WECOM => ['qyapi.weixin.qq.com'],
        self::DINGTALK => ['oapi.dingtalk.com'],
        self::FEISHU => ['open.feishu.cn', 'open.larkoffice.com'],
    ];

    public static function isValid(int $platform): bool
    {
        return in_array($platform, self::ALL, true);
    }

    public static function label(int $platform): string
    {
        return match ($platform) {
            self::WECOM => '企业微信',
            self::DINGTALK => '钉钉',
            self::FEISHU => '飞书',
            default => '未知',
        };
    }

    /**
     * @return list<string>
     */
    public static function webhookHosts(int $platform): array
    {
        return self::WEBHOOK_HOSTS[$platform] ?? [];
    }
}
