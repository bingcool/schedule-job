<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BasePageRequest;

class ListRolesRequest extends BasePageRequest
{
    #[ApiProperty(description: '角色名称关键词')]
    #[ValidationRule(rule: 'nullable|string', message: 'name 格式错误')]
    protected ?string $name = null;

    #[ApiProperty(description: '状态：1=启用，0=禁用')]
    #[ValidationRule(rule: 'nullable|int', message: 'status 必须是整数')]
    #[StringToInt]
    protected ?int $status = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }
}
