<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffUser;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

/**
 * 「生成密码」接口回包：32 位临时密码、到期时间、可用于提示的邮箱。
 */
class GeneratedResetPasswordDto extends AbstractDto
{
    #[ApiProperty(description: '目标用户 ID')]
    protected int $userId = 0;

    #[ApiProperty(description: '32 位临时重置密码')]
    protected string $password = '';

    #[ApiProperty(description: '过期时间')]
    protected string $expiresAt = '';

    #[ApiProperty(description: '用户邮箱，未绑定为空')]
    protected string $email = '';

    public static function of(int $userId, string $password, string $expiresAt, string $email): self
    {
        $dto = new self();
        $dto->userId = $userId;
        $dto->password = $password;
        $dto->expiresAt = $expiresAt;
        $dto->email = $email;

        return $dto;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getExpiresAt(): string
    {
        return $this->expiresAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
