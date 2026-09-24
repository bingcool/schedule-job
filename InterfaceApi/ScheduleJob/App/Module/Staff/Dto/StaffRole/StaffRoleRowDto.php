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
}
