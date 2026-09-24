<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffApiPermissionItemDto extends AbstractDto
{
    #[ApiProperty(description: '权限 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '名称')]
    protected string $name = '';

    #[ApiProperty(description: 'HTTP 方法')]
    protected string $method = '';

    #[ApiProperty(description: '路径')]
    protected string $path = '';

    #[ApiProperty(description: '分组')]
    protected string $group = '';

    /**
     * @return list<self>
     */
    public static function catalog(): array
    {
        $rows = [
            ['id' => 1, 'name' => '任务列表', 'method' => 'GET', 'path' => '/api/v1/tasks', 'group' => '任务管理'],
            ['id' => 2, 'name' => '创建任务', 'method' => 'POST', 'path' => '/api/v1/tasks', 'group' => '任务管理'],
            ['id' => 3, 'name' => '更新任务', 'method' => 'PUT', 'path' => '/api/v1/tasks', 'group' => '任务管理'],
            ['id' => 4, 'name' => '删除任务', 'method' => 'DELETE', 'path' => '/api/v1/tasks', 'group' => '任务管理'],
            ['id' => 5, 'name' => '任务启停', 'method' => 'PUT', 'path' => '/api/v1/tasks/status', 'group' => '任务管理'],
            ['id' => 6, 'name' => '节点列表', 'method' => 'GET', 'path' => '/api/v1/nodes', 'group' => '节点管理'],
            ['id' => 7, 'name' => '执行记录', 'method' => 'GET', 'path' => '/api/v1/tasks/logs', 'group' => '执行记录'],
            ['id' => 8, 'name' => '用户管理', 'method' => 'GET', 'path' => '/api/v1/users', 'group' => '权限管理'],
            ['id' => 9, 'name' => '角色管理', 'method' => 'GET', 'path' => '/api/v1/roles', 'group' => '权限管理'],
            ['id' => 10, 'name' => '菜单管理', 'method' => 'GET', 'path' => '/api/v1/menus', 'group' => '权限管理'],
        ];

        return array_map(static fn (array $row): self => self::fromSlice($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromSlice(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->name = (string) ($row['name'] ?? '');
        $dto->method = (string) ($row['method'] ?? '');
        $dto->path = (string) ($row['path'] ?? '');
        $dto->group = (string) ($row['group'] ?? '');

        return $dto;
    }
}
