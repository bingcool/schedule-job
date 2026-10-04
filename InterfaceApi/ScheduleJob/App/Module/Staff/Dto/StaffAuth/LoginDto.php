<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffAuth;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class LoginDto extends AbstractDto
{
    #[ApiProperty(description: '账号')]
    protected string $account = '';

    #[ApiProperty(description: '密码')]
    protected string $password = '';

    public static function of(string $account, string $password): self
    {
        $dto = new self();
        $dto->account = $account;
        $dto->password = $password;

        return $dto;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setAccount(string $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }
}
