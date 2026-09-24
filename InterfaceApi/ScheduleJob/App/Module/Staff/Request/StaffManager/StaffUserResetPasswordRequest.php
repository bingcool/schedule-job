<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

/**
 * PUT /users/reset-password。确认时只传 id + password，不再要确认密码。
 */
class StaffUserResetPasswordRequest extends BaseRequest
{
    #[ApiProperty(description: '目标用户 ID')]
    #[ValidationRule(rule: 'required|integer|min:1', message: 'id 无效')]
    #[StringToInt]
    protected int $id = 0;

    #[ApiProperty(description: '已生成的临时重置密码')]
    #[ValidationRule(rule: 'required|string', message: 'password 不能为空')]
    protected string $password = '';

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }
}
