<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

interface RobotStrategyInterface
{
    public function send(RobotConfig $config, RobotAlertMessage $message): RobotSendResult;
}
