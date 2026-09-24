<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class CronRobotTestResultDto extends AbstractDto
{
    #[ApiProperty(description: '是否发送成功')]
    protected bool $ok = false;

    #[ApiProperty(description: '失败原因，成功时为空')]
    protected string $error = '';

    public static function of(bool $ok, string $error = ''): self
    {
        $dto = new self();
        $dto->ok = $ok;
        $dto->error = $error;

        return $dto;
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function getError(): string
    {
        return $this->error;
    }
}
