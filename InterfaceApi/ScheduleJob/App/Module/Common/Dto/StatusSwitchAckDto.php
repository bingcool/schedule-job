<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Common\Dto;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StatusSwitchAckDto extends AbstractDto
{
    #[ApiProperty(description: '记录 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '更新后的状态')]
    protected int $status = 0;

    public static function of(int $id, int $status): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->status = $status;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }
}
