<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffUserBriefDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '账号')]
    protected string $account = '';

    #[ApiProperty(description: '用户名称')]
    protected string $userName = '';

    

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function setAccount(string $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }
}
