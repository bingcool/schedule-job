<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffRoleRowDto extends AbstractDto
{
    #[ApiProperty(description: '角色 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '角色名称')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '描述')]
    protected string $desc = '';

    #[ApiProperty(description: '是否超管')]
    protected bool $isSuperRole = false;

    #[ApiProperty(description: '是否系统内置角色')]
    protected bool $isSystemRole = false;

    #[ApiProperty(description: '状态')]
    protected int $status = 1;

    #[ApiProperty(description: '关联用户数')]
    protected int $userCount = 0;

    #[ApiProperty(description: '菜单权限数')]
    protected int $menuCount = 0;

    /**
     * @var array<int, int>
     */
    protected array $pageIds = [];

    /**
     * @var array<int, int>
     */
    protected array $apiPerIds = [];

    /**
     * @var array<int, int>
     */
    protected array $taskPerIds = [];

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    #[ApiProperty(description: '更新时间')]
    protected string $updatedAt = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: '菜单树（详情）')]
    protected array $menus = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: 'API 权限目录（详情）')]
    protected array $apiPermissions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: '任务权限目录（详情）')]
    protected array $taskPermissions = [];

    

    /**
     * @param array<int, array<string, mixed>> $menus
     */
    public function setMenus(array $menus): static
    {
        $this->menus = $menus;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $apiPermissions
     */
    public function setApiPermissions(array $apiPermissions): static
    {
        $this->apiPermissions = $apiPermissions;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $taskPermissions
     */
    public function setTaskPermissions(array $taskPermissions): static
    {
        $this->taskPermissions = $taskPermissions;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getDesc(): string
    {
        return $this->desc;
    }

    public function setDesc(string $desc): static
    {
        $this->desc = $desc;

        return $this;
    }

    public function getIsSuperRole(): bool
    {
        return $this->isSuperRole;
    }

    public function setIsSuperRole(bool $isSuperRole): static
    {
        $this->isSuperRole = $isSuperRole;

        return $this;
    }

    public function getIsSystemRole(): bool
    {
        return $this->isSystemRole;
    }

    public function setIsSystemRole(bool $isSystemRole): static
    {
        $this->isSystemRole = $isSystemRole;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUserCount(): int
    {
        return $this->userCount;
    }

    public function setUserCount(int $userCount): static
    {
        $this->userCount = $userCount;

        return $this;
    }

    public function getMenuCount(): int
    {
        return $this->menuCount;
    }

    public function setMenuCount(int $menuCount): static
    {
        $this->menuCount = $menuCount;

        return $this;
    }

    public function getPageIds(): array
    {
        return $this->pageIds;
    }

    public function setPageIds(array $pageIds): static
    {
        $this->pageIds = $pageIds;

        return $this;
    }

    public function getApiPerIds(): array
    {
        return $this->apiPerIds;
    }

    public function setApiPerIds(array $apiPerIds): static
    {
        $this->apiPerIds = $apiPerIds;

        return $this;
    }

    public function getTaskPerIds(): array
    {
        return $this->taskPerIds;
    }

    public function setTaskPerIds(array $taskPerIds): static
    {
        $this->taskPerIds = $taskPerIds;

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getMenus(): array
    {
        return $this->menus;
    }

    public function getApiPermissions(): array
    {
        return $this->apiPermissions;
    }

    public function getTaskPermissions(): array
    {
        return $this->taskPermissions;
    }
}
