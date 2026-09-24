<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffRoleBriefDto extends AbstractDto
{
    #[ApiProperty(description: '角色 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '角色名称')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '是否超管角色')]
    protected bool $isSuperRole = false;

    

    

    public function getId(): int
    {
        return $this->id;
    }

    public function getIsSuperRole(): bool
    {
        return $this->isSuperRole;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
