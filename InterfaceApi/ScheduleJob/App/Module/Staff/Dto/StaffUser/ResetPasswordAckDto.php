<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 「确认重置」结果：密码已写入；mailSent 表示邮件是否真正发出。
 */
class ResetPasswordAckDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '密码是否已更新')]
    protected bool $changed = true;

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

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getChanged(): bool
    {
        return $this->changed;
    }

    public function setChanged(bool $changed): static
    {
        $this->changed = $changed;

        return $this;
    }

    public function setMailSent(bool $mailSent): static
    {
        $this->mailSent = $mailSent;

        return $this;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
}
