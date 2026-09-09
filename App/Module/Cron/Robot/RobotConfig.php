<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

final class RobotConfig
{
    public function __construct(
        public readonly int $id,
        public readonly int $platform,
        public readonly string $webhookUrl,
        public readonly string $secret,
    ) {
    }
}
