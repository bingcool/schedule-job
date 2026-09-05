<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class ResetUserPasswordDto extends AbstractDto
{
    #[ApiProperty(description: '目标用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '新密码')]
    protected string $newPassword = '';

    #[ApiProperty(description: '确认新密码')]
    protected string $newPasswordConfirm = '';

    public static function of(int $id, string $newPassword, string $newPasswordConfirm): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->newPassword = $newPassword;
        $dto->newPasswordConfirm = $newPasswordConfirm;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
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
