<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class RobotIdDto extends AbstractDto
{
    #[ApiProperty(description: 'cron_robot 主键')]
    protected int $id = 0;

    public static function of(int $id): self
    {
        $dto = new self();
        $dto->id = $id;

        return $dto;
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
}
