<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class LoginRequest extends BaseRequest
{
    #[ApiProperty(description: '登录标识：含 @ 按邮箱验证，否则按账号验证')]
    #[ValidationRule(rule: 'required|string', message: 'account 不能为空')]
    protected string $account = '';

    #[ApiProperty(description: '密码')]
    #[ValidationRule(rule: 'required|string', message: 'password 不能为空')]
    protected string $password = '';

    public function getAccount(): string
    {
        return trim($this->account);
    }

    public function setAccount(string $account): static
    {
        $this->account = $account;

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
