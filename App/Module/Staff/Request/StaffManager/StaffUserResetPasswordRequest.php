<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffUserResetPasswordRequest extends BaseRequest
{
    #[ApiProperty(description: '目标用户 ID')]
    #[ValidationRule(rule: 'required|integer|min:1', message: 'id 无效')]
    protected int $id = 0;

    #[ApiProperty(description: '新密码')]
    #[ValidationRule(rule: 'required|string', message: 'newPassword 不能为空')]
    protected string $newPassword = '';

    #[ApiProperty(description: '确认新密码')]
    #[ValidationRule(rule: 'required|string', message: 'newPasswordConfirm 不能为空')]
    protected string $newPasswordConfirm = '';

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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
