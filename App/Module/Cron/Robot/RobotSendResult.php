<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

final class RobotSendResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly int $httpStatus,
        public readonly string $error = '',
    ) {
    }

    public static function success(int $httpStatus): self
    {
        return new self(true, $httpStatus);
    }

    public static function fail(int $httpStatus, string $error): self
    {
        return new self(false, $httpStatus, $error);
    }
}
