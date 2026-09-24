<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class SwitchMenuStatusDto extends AbstractDto
{
    #[ApiProperty(description: '菜单 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '1=启用，0=禁用')]
    protected int $status = 1;

    public static function of(int $id, int $status): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->status = $status === 0 ? 0 : 1;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
