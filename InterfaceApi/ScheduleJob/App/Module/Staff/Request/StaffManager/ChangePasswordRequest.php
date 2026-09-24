<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class ChangePasswordRequest extends BaseRequest
{
    #[ApiProperty(description: '旧密码')]
    #[ValidationRule(rule: 'required|string', message: '旧密码不能为空')]
    protected string $oldPassword = '';

    #[ApiProperty(description: '新密码')]
    #[ValidationRule(rule: 'required|string', message: '新密码不能为空')]
    protected string $newPassword = '';

    #[ApiProperty(description: '确认新密码')]
    #[ValidationRule(rule: 'required|string', message: '确认新密码不能为空')]
    protected string $newPasswordConfirm = '';

    public function getOldPassword(): string
    {
        return $this->oldPassword;
    }

    public function setOldPassword(string $oldPassword): static
    {
        $this->oldPassword = $oldPassword;

        return $this;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): static
    {
        $this->newPassword = $newPassword;

        return $this;
    }

    public function getNewPasswordConfirm(): string
    {
        return $this->newPasswordConfirm;
    }

    public function setNewPasswordConfirm(string $newPasswordConfirm): static
    {
        $this->newPasswordConfirm = $newPasswordConfirm;

        return $this;
    }
}
