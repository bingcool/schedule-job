<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class ChangePasswordDto extends AbstractDto
{
    #[ApiProperty(description: '旧密码')]
    protected string $oldPassword = '';

    #[ApiProperty(description: '新密码')]
    protected string $newPassword = '';

    #[ApiProperty(description: '确认新密码')]
    protected string $newPasswordConfirm = '';

    public static function of(string $oldPassword, string $newPassword, string $newPasswordConfirm): self
    {
        $dto = new self();
        $dto->oldPassword = $oldPassword;
        $dto->newPassword = $newPassword;
        $dto->newPasswordConfirm = $newPasswordConfirm;

        return $dto;
    }

    public function getOldPassword(): string
    {
        return $this->oldPassword;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function getNewPasswordConfirm(): string
    {
        return $this->newPasswordConfirm;
    }
}
