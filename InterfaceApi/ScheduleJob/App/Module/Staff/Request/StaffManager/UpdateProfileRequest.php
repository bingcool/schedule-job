<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    #[ApiProperty(description: '用户名称')]
    #[ValidationRule(rule: 'required|string', message: '用户名称不能为空')]
    protected string $userName = '';

    public function getUserName(): string
    {
        return trim($this->userName);
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }
}
