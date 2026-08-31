<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

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
}
