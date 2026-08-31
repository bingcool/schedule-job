<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class CreateUserDto extends AbstractDto
{
    #[ApiProperty(description: '账号')]
    protected string $account = '';

    #[ApiProperty(description: '用户名称')]
    protected string $userName = '';

    #[ApiProperty(description: '明文密码')]
    protected string $password = '';

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '角色 ID')]
    protected array $roleIds = [];

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '节点组 ID')]
    protected array $nodeGroupIds = [];

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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return array<int, int>
     */
    public function getRoleIds(): array
    {
        return $this->roleIds;
    }

    /**
     * @param array<int, int> $roleIds
     */
    public function setRoleIds(array $roleIds): static
    {
        $this->roleIds = $roleIds;

        return $this;
    }

    /**
     * @return array<int, int>
     */
    public function getNodeGroupIds(): array
    {
        return $this->nodeGroupIds;
    }

    /**
     * @param array<int, int> $nodeGroupIds
     */
    public function setNodeGroupIds(array $nodeGroupIds): static
    {
        $this->nodeGroupIds = $nodeGroupIds;

        return $this;
    }
}
