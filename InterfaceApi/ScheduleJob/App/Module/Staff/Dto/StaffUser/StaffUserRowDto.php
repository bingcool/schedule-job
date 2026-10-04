<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffUserRowDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '账号')]
    protected string $account = '';

    #[ApiProperty(description: '邮箱，账号非邮箱时为空')]
    protected string $email = '';

    #[ApiProperty(description: '用户名称')]
    protected string $userName = '';

    #[ApiProperty(description: '1=正常，0=已禁用')]
    protected int $status = 1;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: '角色列表')]
    protected array $roles = [];

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

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: '授权节点组')]
    protected array $nodeGroups = [];

    #[ApiProperty(description: '是否超级管理员')]
    protected bool $isSuper = false;

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    #[ApiProperty(description: '更新时间')]
    protected string $updatedAt = '';

    

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return array<int, int>
     */
    public function getRoleIds(): array
    {
        return $this->roleIds;
    }

    /**
     * @return array<int, int>
     */
    public function getNodeGroupIds(): array
    {
        return $this->nodeGroupIds;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNodeGroups(): array
    {
        return $this->nodeGroups;
    }

    public function getIsSuper(): bool
    {
        return $this->isSuper;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setAccount(string $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function setRoleIds(array $roleIds): static
    {
        $this->roleIds = $roleIds;

        return $this;
    }

    public function setNodeGroupIds(array $nodeGroupIds): static
    {
        $this->nodeGroupIds = $nodeGroupIds;

        return $this;
    }

    public function setNodeGroups(array $nodeGroups): static
    {
        $this->nodeGroups = $nodeGroups;

        return $this;
    }

    public function setIsSuper(bool $isSuper): static
    {
        $this->isSuper = $isSuper;

        return $this;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
