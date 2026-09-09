<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

use App\Module\Cron\Exception\CronTaskException;
use App\Module\Cron\RobotPlatform;

final class RobotStrategyFactory
{
    public function make(int $platform): RobotStrategyInterface
    {
        return match ($platform) {
            RobotPlatform::WECOM => new WeComRobotStrategy(),
            RobotPlatform::DINGTALK => new DingTalkRobotStrategy(),
            RobotPlatform::FEISHU => new FeishuRobotStrategy(),
            default => throw CronTaskException::throw('不支持的机器人平台', 422),
        };
    }
}
