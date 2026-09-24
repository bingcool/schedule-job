<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class MenuIdDto extends AbstractDto
{
    #[ApiProperty(description: '菜单 ID')]
    protected int $id = 0;

    public static function of(int $id): self
    {
        $dto = new self();
        $dto->id = $id;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
