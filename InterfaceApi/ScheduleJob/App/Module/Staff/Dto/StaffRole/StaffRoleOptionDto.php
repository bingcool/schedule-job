<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

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

    

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getIsSuper(): bool
    {
        return $this->isSuper;
    }

    public function setIsSuper(bool $isSuper): static
    {
        $this->isSuper = $isSuper;

        return $this;
    }
}
