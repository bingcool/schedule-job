<?php

declare(strict_types=1);

namespace App\Module\Staff\Assembler;

use App\Module\Staff\Entity\StaffRoleEntity;
use App\Module\Staff\Entity\StaffUserEntity;
use App\Module\Staff\StaffRoleCode;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffMenuRowDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffRoleBriefDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffRoleOptionDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffRoleRowDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffApiPermissionItemDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffTaskPermissionItemDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\StaffNodeGroupBriefDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\StaffUserBriefDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\StaffUserRowDto;

final class StaffContractDtoAssembler
{
    public static function userBriefFromEntity(StaffUserEntity $user): StaffUserBriefDto
    {
        $dto = new StaffUserBriefDto();
        $dto->copyProperty([
            'id' => (int) $user->id,
            'account' => (string) $user->account,
            'userName' => (string) $user->user_name,
        ]);

        return $dto;
    }

    public static function roleOptionFromEntity(StaffRoleEntity $role): StaffRoleOptionDto
    {
        $dto = new StaffRoleOptionDto();
        $dto->copyProperty([
            'id' => (int) $role->id,
            'name' => (string) $role->name,
            'code' => (string) $role->code,
            'isSuper' => (int) ($role->is_super_role ?? 0) === 1,
        ]);

        return $dto;
    }

    public static function roleBriefFromEntity(StaffRoleEntity $role): StaffRoleBriefDto
    {
        $dto = new StaffRoleBriefDto();
        $dto->copyProperty([
            'id' => (int) $role->id,
            'name' => (string) $role->name,
            'code' => (string) $role->code,
            'isSuperRole' => (int) ($role->is_super_role ?? 0) === 1,
        ]);

        return $dto;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function roleBriefFromSlice(array $row): StaffRoleBriefDto
    {
        $dto = new StaffRoleBriefDto();
        $dto->copyProperty([
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'isSuperRole' => !empty($row['isSuperRole']) || (int) ($row['is_super_role'] ?? 0) === 1,
        ]);

        return $dto;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function userRowFromEntityRow(array $row): StaffUserRowDto
    {
        $dto = new StaffUserRowDto();
        $dto->copyProperty([
            'id' => (int) ($row['id'] ?? 0),
            'account' => (string) ($row['account'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'userName' => (string) ($row['user_name'] ?? $row['userName'] ?? ''),
            'status' => (int) ($row['status'] ?? 1) === 0 ? 0 : 1,
            'roles' => is_array($row['roles'] ?? null) ? $row['roles'] : [],
            'roleIds' => array_values(array_map('intval', $row['role_ids'] ?? $row['roleIds'] ?? [])),
            'nodeGroupIds' => array_values(array_map('intval', $row['node_group_ids'] ?? $row['nodeGroupIds'] ?? [])),
            'nodeGroups' => is_array($row['node_groups'] ?? $row['nodeGroups'] ?? null)
                ? ($row['node_groups'] ?? $row['nodeGroups'] ?? [])
                : [],
            'isSuper' => (bool) ($row['is_super'] ?? $row['isSuper'] ?? false),
            'createdAt' => (string) ($row['created_at'] ?? $row['createdAt'] ?? ''),
            'updatedAt' => (string) ($row['updated_at'] ?? $row['updatedAt'] ?? ''),
        ]);

        return $dto;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function roleRowFromEntityRow(array $row): StaffRoleRowDto
    {
        $code = (string) ($row['code'] ?? '');
        $dto = new StaffRoleRowDto();
        $dto->copyProperty([
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'code' => $code,
            'desc' => (string) ($row['desc'] ?? ''),
            'isSuperRole' => (int) ($row['is_super_role'] ?? $row['isSuperRole'] ?? 0) === 1,
            'isSystemRole' => StaffRoleCode::isSystem($code),
            'status' => (int) ($row['status'] ?? 1),
            'userCount' => (int) ($row['user_count'] ?? $row['userCount'] ?? 0),
            'menuCount' => (int) ($row['menu_count'] ?? $row['menuCount'] ?? 0),
            'pageIds' => array_values(array_map('intval', $row['page_ids'] ?? $row['pageIds'] ?? [])),
            'apiPerIds' => array_values(array_map('intval', $row['api_per_ids'] ?? $row['apiPerIds'] ?? [])),
            'taskPerIds' => array_values(array_map('intval', $row['task_per_ids'] ?? $row['taskPerIds'] ?? [])),
            'createdAt' => (string) ($row['created_at'] ?? $row['createdAt'] ?? ''),
            'updatedAt' => (string) ($row['updated_at'] ?? $row['updatedAt'] ?? ''),
        ]);
        if (isset($row['menus']) && is_array($row['menus'])) {
            $dto->setMenus($row['menus']);
        }
        if (isset($row['apiPermissions']) && is_array($row['apiPermissions'])) {
            $dto->setApiPermissions($row['apiPermissions']);
        }
        if (isset($row['taskPermissions']) && is_array($row['taskPermissions'])) {
            $dto->setTaskPermissions($row['taskPermissions']);
        }

        return $dto;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function menuRowFromEntityRow(array $row): StaffMenuRowDto
    {
        $dto = new StaffMenuRowDto();
        $dto->copyProperty([
            'id' => (int) ($row['id'] ?? 0),
            'appId' => (int) ($row['app_id'] ?? $row['appId'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'parentPrefix' => (string) ($row['parent_prefix'] ?? $row['parentPrefix'] ?? ''),
            'parentId' => (int) ($row['parent_id'] ?? $row['parentId'] ?? 0),
            'uri' => (string) ($row['uri'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'icon' => (string) ($row['icon'] ?? ''),
            'sort' => (int) ($row['sort'] ?? 0),
            'status' => (int) ($row['status'] ?? 1),
            'createdAt' => (string) ($row['created_at'] ?? $row['createdAt'] ?? ''),
        ]);

        return $dto;
    }

    /**
     * @param array{id?: int, groupName?: string, group_name?: string} $row
     */
    public static function nodeGroupBriefFromSlice(array $row): StaffNodeGroupBriefDto
    {
        $dto = new StaffNodeGroupBriefDto();
        $dto->copyProperty([
            'id' => (int) ($row['id'] ?? 0),
            'groupName' => (string) ($row['groupName'] ?? $row['group_name'] ?? ''),
        ]);

        return $dto;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function apiPermissionFromSlice(array $row): StaffApiPermissionItemDto
    {
        $dto = new StaffApiPermissionItemDto();
        $dto->copyProperty([
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'method' => (string) ($row['method'] ?? ''),
            'path' => (string) ($row['path'] ?? ''),
            'group' => (string) ($row['group'] ?? ''),
        ]);

        return $dto;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function taskPermissionFromSlice(array $row): StaffTaskPermissionItemDto
    {
        $dto = new StaffTaskPermissionItemDto();
        $dto->copyProperty([
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'desc' => (string) ($row['desc'] ?? ''),
        ]);

        return $dto;
    }

    /**
     * @return list<StaffApiPermissionItemDto>
     */
    public static function apiPermissionCatalog(): array
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

        return array_map(static fn (array $row): StaffApiPermissionItemDto => self::apiPermissionFromSlice($row), $rows);
    }

    /**
     * @return list<StaffTaskPermissionItemDto>
     */
    public static function taskPermissionCatalog(): array
    {
        $rows = [
            ['id' => 1, 'name' => '立即执行', 'code' => 'cron:task:run_once', 'desc' => '手动触发任务执行'],
            ['id' => 2, 'name' => '启用/禁用', 'code' => 'cron:task:switch', 'desc' => '切换任务启用状态'],
            ['id' => 3, 'name' => '查看日志', 'code' => 'cron:task:logs', 'desc' => '查看任务执行日志'],
            ['id' => 4, 'name' => '编辑 GLUE', 'code' => 'cron:task:glue_edit', 'desc' => '编辑 GLUE 脚本内容'],
        ];

        return array_map(static fn (array $row): StaffTaskPermissionItemDto => self::taskPermissionFromSlice($row), $rows);
    }
}
