<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BasePageRequest;

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
