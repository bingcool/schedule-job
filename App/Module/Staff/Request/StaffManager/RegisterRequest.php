<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class RegisterRequest extends BaseRequest
{
    #[ApiProperty(description: '账号（建议邮箱）')]
    #[ValidationRule(rule: 'required|string', message: 'account 不能为空')]
    protected string $account = '';

    #[ApiProperty(description: '用户名称')]
    #[ValidationRule(rule: 'required|string', message: 'userName 不能为空')]
    protected string $userName = '';

    #[ApiProperty(description: '密码')]
    #[ValidationRule(rule: 'required|string', message: 'password 不能为空')]
    protected string $password = '';

    #[ApiProperty(description: '确认密码')]
    #[ValidationRule(rule: 'required|string', message: 'passwordConfirm 不能为空')]
    protected string $passwordConfirm = '';

    public function getAccount(): string
    {
        return trim($this->account);
    }

    public function setAccount(string $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getUserName(): string
    {
        return trim($this->userName);
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

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

    public function getPasswordConfirm(): string
    {
        return $this->passwordConfirm;
    }

    public function setPasswordConfirm(string $passwordConfirm): static
    {
        $this->passwordConfirm = $passwordConfirm;

        return $this;
    }
}
