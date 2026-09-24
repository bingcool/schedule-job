<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffMenuRowDto extends AbstractDto
{
    #[ApiProperty(description: '菜单 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '应用 ID')]
    protected int $appId = 0;

    #[ApiProperty(description: '菜单名称')]
    protected string $name = '';

    #[ApiProperty(description: '父路径')]
    protected string $parentPrefix = '';

    #[ApiProperty(description: '父菜单 ID')]
    protected int $parentId = 0;

    #[ApiProperty(description: 'URI')]
    protected string $uri = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '图标')]
    protected string $icon = '';

    #[ApiProperty(description: '排序')]
    protected int $sort = 0;

    #[ApiProperty(description: '状态')]
    protected int $status = 1;

    /**
     * @var array<int, StaffMenuRowDto>
     */
    #[ApiProperty(description: '子菜单')]
    protected array $children = [];

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    

    public function getId(): int
    {
        return $this->id;
    }

    public function addChild(self $child): void
    {
        $this->children[] = $child;
    }

    /**
     * @return array<int, StaffMenuRowDto>
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
