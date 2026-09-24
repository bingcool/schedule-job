<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class SwitchRobotStatusDto extends AbstractDto
{
    #[ApiProperty(description: '机器人 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '0-禁用 1-启用')]
    protected int $status = 1;

    public function getId(): int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getStatus(): int { return $this->status; }
    public function setStatus(int $status): static { $this->status = $status; return $this; }
}
