<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * 群机器人 Webhook 超时。三个 Strategy 共用，禁止各自写死数字。
 */
final class RobotAlertConfig
{
    public const CONNECT_DEFAULT = 2;

    public const REQUEST_DEFAULT = 5;

    public static function connectTimeout(): int
    {
        $raw = env('ROBOT_CONNECT_TIMEOUT');
        if ($raw === null || $raw === '') {
            return self::CONNECT_DEFAULT;
        }

        return max(1, (int) $raw);
    }

    public static function requestTimeout(): int
    {
        $raw = env('ROBOT_REQUEST_TIMEOUT');
        if ($raw === null || $raw === '') {
            return self::REQUEST_DEFAULT;
        }

        return max(1, (int) $raw);
    }
}
