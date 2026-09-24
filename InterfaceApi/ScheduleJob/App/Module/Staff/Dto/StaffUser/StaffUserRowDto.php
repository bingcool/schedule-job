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
}
