<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class LoginRequest extends BaseRequest
{
    #[ApiProperty(description: '账号（建议邮箱）')]
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
