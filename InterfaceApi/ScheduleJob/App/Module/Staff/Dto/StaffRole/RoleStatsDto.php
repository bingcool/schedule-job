<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class RoleStatsDto extends AbstractDto
{
    #[ApiProperty(description: '角色总数')]
    protected int $total = 0;

    #[ApiProperty(description: '启用数')]
    protected int $enabled = 0;

    #[ApiProperty(description: '禁用数')]
    protected int $disabled = 0;

    #[ApiProperty(description: '超管角色数')]
    protected int $super = 0;

    #[ApiProperty(description: '已分配角色的用户数')]
    protected int $userCount = 0;

    public static function of(int $total, int $enabled, int $disabled, int $super, int $userCount): self
    {
        $dto = new self();
        $dto->total = $total;
        $dto->enabled = $enabled;
        $dto->disabled = $disabled;
        $dto->super = $super;
        $dto->userCount = $userCount;

        return $dto;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getEnabled(): int
    {
        return $this->enabled;
    }

    public function setEnabled(int $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getDisabled(): int
    {
        return $this->disabled;
    }

    public function setDisabled(int $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function getSuper(): int
    {
        return $this->super;
    }

    public function setSuper(int $super): static
    {
        $this->super = $super;

        return $this;
    }

    public function getUserCount(): int
    {
        return $this->userCount;
    }

    public function setUserCount(int $userCount): static
    {
        $this->userCount = $userCount;

        return $this;
    }
}
