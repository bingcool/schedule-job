<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\ScheduleJob\App\Module\Staff\Entity\StaffRoleEntity;
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

    public static function fromRoleEntity(StaffRoleEntity $role): self
    {
        $dto = new self();
        $dto->id = (int) $role->id;
        $dto->name = (string) $role->name;
        $dto->code = (string) $role->code;
        $dto->isSuperRole = (int) ($role->is_super_role ?? 0) === 1;

        return $dto;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromSlice(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->name = (string) ($row['name'] ?? '');
        $dto->code = (string) ($row['code'] ?? '');
        $dto->isSuperRole = !empty($row['isSuperRole']) || (int) ($row['is_super_role'] ?? 0) === 1;

        return $dto;
    }

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
