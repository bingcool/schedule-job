<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class StaffUserRowDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '账号')]
    protected string $account = '';

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

    #[ApiProperty(description: '是否超级管理员')]
    protected bool $isSuper = false;

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    #[ApiProperty(description: '更新时间')]
    protected string $updatedAt = '';

    /**
     * @param array<string, mixed> $row
     */
    public static function fromEntityRow(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->account = (string) ($row['account'] ?? '');
        $dto->userName = (string) ($row['user_name'] ?? $row['userName'] ?? '');
        $dto->status = (int) ($row['status'] ?? 1) === 0 ? 0 : 1;
        $dto->roles = is_array($row['roles'] ?? null) ? $row['roles'] : [];
        $dto->roleIds = array_values(array_map('intval', $row['role_ids'] ?? $row['roleIds'] ?? []));
        $dto->nodeGroupIds = array_values(array_map('intval', $row['node_group_ids'] ?? $row['nodeGroupIds'] ?? []));
        $dto->isSuper = (bool) ($row['is_super'] ?? $row['isSuper'] ?? false);
        $dto->createdAt = (string) ($row['created_at'] ?? $row['createdAt'] ?? '');
        $dto->updatedAt = (string) ($row['updated_at'] ?? $row['updatedAt'] ?? '');

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccount(): string
    {
        return $this->account;
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
