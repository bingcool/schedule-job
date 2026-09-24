<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\ScheduleJob\App\Module\Staff\Entity\StaffRoleEntity;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffRoleOptionDto extends AbstractDto
{
    #[ApiProperty(description: '角色 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '角色名称')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '是否超管角色')]
    protected bool $isSuper = false;

    public static function fromRoleEntity(StaffRoleEntity $role): self
    {
        $dto = new self();
        $dto->id = (int) $role->id;
        $dto->name = (string) $role->name;
        $dto->code = (string) $role->code;
        $dto->isSuper = (int) ($role->is_super_role ?? 0) === 1;

        return $dto;
    }
}
