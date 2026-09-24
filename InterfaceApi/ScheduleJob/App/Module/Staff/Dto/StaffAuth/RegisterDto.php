<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffAuth;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class RegisterDto extends AbstractDto
{
    #[ApiProperty(description: '账号')]
    protected string $account = '';

    #[ApiProperty(description: '用户名称')]
    protected string $userName = '';

    #[ApiProperty(description: '密码')]
    protected string $password = '';

    #[ApiProperty(description: '确认密码')]
    protected string $passwordConfirm = '';

    public static function of(string $account, string $userName, string $password, string $passwordConfirm): self
    {
        $dto = new self();
        $dto->account = $account;
        $dto->userName = $userName;
        $dto->password = $password;
        $dto->passwordConfirm = $passwordConfirm;

        return $dto;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirm(): string
    {
        return $this->passwordConfirm;
    }
}
