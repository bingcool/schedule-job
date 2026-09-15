<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

/**
 * 「确认重置」结果：密码已写入；mailSent 表示邮件是否真正发出。
 */
class ResetPasswordAckDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '是否已发邮件')]
    protected bool $mailSent = false;

    #[ApiProperty(description: '接收邮箱')]
    protected string $email = '';

    public static function of(int $id, bool $mailSent, string $email): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->mailSent = $mailSent;
        $dto->email = $email;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMailSent(): bool
    {
        return $this->mailSent;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
