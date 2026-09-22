<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use App\Module\Staff\Dto\StaffManager\GrantRolePagesDto;
use App\Module\Staff\Dto\StaffManager\CreateMenuDto;
use App\Module\Staff\Dto\StaffManager\CreateRoleDto;
use App\Module\Staff\Dto\StaffManager\ListRolesQueryDto;
use App\Module\Staff\Dto\StaffManager\MenuIdDto;
use App\Module\Staff\Dto\StaffManager\RoleIdDto;
use App\Module\Staff\Dto\StaffManager\StaffMenuRowDto;
use App\Module\Staff\Dto\StaffManager\StaffRoleRowDto;
use App\Module\Staff\Dto\StaffManager\SortMenusDto;
use App\Module\Staff\Dto\StaffManager\SwitchMenuStatusDto;
use App\Module\Staff\Dto\StaffManager\SwitchRoleStatusDto;
use App\Module\Staff\Dto\StaffManager\UpdateMenuDto;
use App\Module\Staff\Dto\StaffManager\UpdateRoleDto;
use App\Module\Staff\Entity\StaffMenuPageEntity;
use App\Module\Staff\Entity\StaffRoleEntity;
use App\Module\Staff\Exception\StaffException;
use App\Module\Staff\Repository\StaffMenuPageRepository;
use App\Module\Staff\Repository\StaffRolePageRepository;
use App\Module\Staff\Repository\StaffRolePermissionRepository;
use App\Module\Staff\Repository\StaffRoleRepository;
use App\Module\Staff\Repository\StaffUserRoleRepository;
use App\Module\Staff\Response\StaffManager\ListRolesPageResult;
use App\Module\Staff\StaffApp;
use App\Module\Staff\StaffRoleCode;

class StaffRoleService
{
    private StaffRoleRepository $roleRepository {
        get => $this->roleRepository ??= new StaffRoleRepository();
    }

    private StaffUserRoleRepository $userRoleRepository {
        get => $this->userRoleRepository ??= new StaffUserRoleRepository();
    }

    private StaffMenuPageRepository $menuRepository {
        get => $this->menuRepository ??= new StaffMenuPageRepository();
    }

    private StaffRolePageRepository $rolePageRepository {
        get => $this->rolePageRepository ??= new StaffRolePageRepository();
    }

    private StaffRolePermissionRepository $rolePermissionRepository {
        get => $this->rolePermissionRepository ??= new StaffRolePermissionRepository();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function apiPermissionCatalog(): array
    {
        return [
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
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function taskPermissionCatalog(): array
    {
        return [
            ['id' => 1, 'name' => '立即执行', 'code' => 'cron:task:run_once', 'desc' => '手动触发任务执行'],
            ['id' => 2, 'name' => '启用/禁用', 'code' => 'cron:task:switch', 'desc' => '切换任务启用状态'],
            ['id' => 3, 'name' => '查看日志', 'code' => 'cron:task:logs', 'desc' => '查看任务执行日志'],
            ['id' => 4, 'name' => '编辑 GLUE', 'code' => 'cron:task:glue_edit', 'desc' => '编辑 GLUE 脚本内容'],
        ];
    }

    public function listRoles(ListRolesQueryDto $query): ListRolesPageResult
    {
        $this->ensureSystemRole(
            StaffRoleCode::EDITOR_TASK_GROUP,
            '任务编辑组',
            '可编辑计划任务（含他人创建的任务），适用于同事离职后的任务维护',
            false,
        );

        $name = trim((string) ($query->getName() ?? ''));
        $status = $query->getStatus();
        $appId = StaffApp::appId();

        $pageResult = new ListRolesPageResult();
        $pageResult->setPage($query->getPage());
        $pageResult->setPageSize($query->getPageSize());
        $pageResult->setTotal($this->roleRepository->countByListQuery($query));

        $rows = $this->roleRepository->listRowsByListQuery($query);
        $roleIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $userCounts = $this->userRoleRepository->countUsersGroupedByRoleIds($roleIds);
        $menuCounts = $this->rolePageRepository->countPagesGroupedByRoleIds($roleIds);

        foreach ($rows as $row) {
            $roleId = (int) $row['id'];
            $row['user_count'] = $userCounts[$roleId] ?? 0;
            $row['menu_count'] = $menuCounts[$roleId] ?? 0;
            $pageResult->addListItem(StaffRoleRowDto::fromEntityRow($row));
        }

        return $pageResult;
    }

    /**
     * @return array<string, int>
     */
    public function roleStats(): array
    {
        $roles = $this->roleRepository->listAllRowsForApp();
        $enabled = 0;
        $super = 0;
        foreach ($roles as $role) {
            if ((int) ($role['status'] ?? 0) === 1) {
                $enabled++;
            }
            if ((int) ($role['is_super_role'] ?? 0) === 1) {
                $super++;
            }
        }
        $userCount = $this->userRoleRepository->countByApp();

        return [
            'total' => count($roles),
            'enabled' => $enabled,
            'disabled' => count($roles) - $enabled,
            'super' => $super,
            'userCount' => $userCount,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRoleOptions(): array
    {
        $rows = $this->roleRepository->listEnabledOptionRows();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'code' => (string) $row['code'],
                'isSuper' => (int) ($row['is_super_role'] ?? 0) === 1,
            ];
        }

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    public function createRole(CreateRoleDto $dto): array
    {
        $name = $dto->getName();
        $code = $dto->getCode();
        if ($name === '' || $code === '') {
            throw StaffException::throw('角色名称和唯一标识不能为空', -1);
        }
        // SoftDelete 下 loadByCode 只命中未删除行，已删角色的 code 可复用
        if ($this->roleRepository->findByCode($code)) {
            throw StaffException::throw('角色标识已存在', -1);
        }
        if (StaffRoleCode::isSystem($code)) {
            throw StaffException::throw('不能使用系统保留的角色标识', -1);
        }

        $role = $this->roleRepository->insert([
            'app_id' => StaffApp::appId(),
            'name' => $name,
            'code' => $code,
            'desc' => $dto->getDesc(),
            'status' => $dto->getStatus(),
            'is_super_role' => 0,
        ]);

        return $this->getRole(RoleIdDto::of((int) $role->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function updateRole(UpdateRoleDto $dto): array
    {
        $id = $dto->getId();
        $role = $this->requireRole($id);
        $code = $dto->getCode();
        if ($dto->getName() === '' || $code === '') {
            throw StaffException::throw('角色名称和唯一标识不能为空', -1);
        }
        if ($code !== (string) $role->code) {
            throw StaffException::throw('角色唯一标识创建后不可修改', -1);
        }

        $this->roleRepository->save($role->setData([
            'name' => $dto->getName(),
            'desc' => $dto->getDesc(),
            'status' => $this->isStatusLockedRole($role) ? 1 : $dto->getStatus(),
        ]));

        return $this->getRole(RoleIdDto::of($id));
    }

    /**
     * 独立配置角色的菜单页面权限（staff_role_page）。
     *
     * @return array<string, mixed>
     */
    public function grantRolePages(GrantRolePagesDto $dto): array
    {
        $role = $this->requireRole($dto->getId());
        if ($role->isSuperRole()) {
            throw StaffException::throw('超级管理员角色拥有全部菜单，无需配置', -1);
        }

        $pageIds = array_values(array_filter($dto->getPageIds(), static fn (int $id): bool => $id > 0));
        if ($pageIds !== []) {
            $rows = $this->menuRepository->listVisibleRowsByIds($pageIds);
            if (count($rows) !== count(array_unique($pageIds))) {
                throw StaffException::throw('菜单页面不存在或已失效', -1);
            }
        }

        $this->replaceRolePages($dto->getId(), $pageIds);

        return $this->getRole(RoleIdDto::of($dto->getId()));
    }

    /**
     * @return array<string, mixed>
     */
    public function getRole(RoleIdDto $dto): array
    {
        $role = $this->requireRole($dto->getId());
        $attrs = $role->getAttributes();
        $attrs['page_ids'] = $this->pageIdsOfRole((int) $role->id);
        $attrs['api_per_ids'] = $this->permissionIdsOfRole((int) $role->id, StaffApp::PERMISSION_TYPE_API);
        $attrs['task_per_ids'] = $this->permissionIdsOfRole((int) $role->id, StaffApp::PERMISSION_TYPE_TASK);
        $attrs['user_count'] = $this->countUsersByRoleIds([(int) $role->id])[(int) $role->id] ?? 0;
        $attrs['menu_count'] = count($attrs['page_ids']);
        $attrs['menus'] = $this->menuTreeArrays();
        $attrs['apiPermissions'] = self::apiPermissionCatalog();
        $attrs['taskPermissions'] = self::taskPermissionCatalog();

        return $attrs;
    }

    public function deleteRole(RoleIdDto $dto): int
    {
        $role = $this->requireRole($dto->getId());
        if ($role->isSuperRole() || StaffRoleCode::isSystem((string) $role->code)) {
            throw StaffException::throw('系统角色不能删除', -1);
        }
        $userCount = $this->countUsersByRoleIds([(int) $role->id])[(int) $role->id] ?? 0;
        if ($userCount > 0) {
            throw StaffException::throw('角色已被 ' . $userCount . ' 个用户关联使用，无法删除', -1);
        }

        $this->rolePageRepository->deleteByRoleId((int) $role->id);
        $this->rolePermissionRepository->deleteByRoleId((int) $role->id);
        // SoftDelete：写入 deleted_at，列表 / loadById / loadByCode 自动排除
        $this->roleRepository->delete($role);

        return (int) $role->id;
    }

    public function switchStatus(SwitchRoleStatusDto $dto): SwitchRoleStatusDto
    {
        $role = $this->requireRole($dto->getId());
        $status = $dto->getStatus();
        if ($status === 0 && $this->isStatusLockedRole($role)) {
            throw StaffException::throw('系统角色不能禁用', -1);
        }

        $this->roleRepository->save($role->setData(['status' => $status]));

        return SwitchRoleStatusDto::of((int) $role->id, $status);
    }

    /**
     * @return array<int, StaffMenuRowDto>
     */
    public function listMenus(): array
    {
        $this->reconcileNavMenus();

        return $this->buildMenuTree($this->loadMenuRows());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menuTreeArrays(): array
    {
        $tree = [];
        foreach ($this->listMenus() as $dto) {
            $tree[] = $dto->toDeepArray();
        }

        return $tree;
    }

    /**
     * @return array<string, mixed>
     */
    public function createMenu(CreateMenuDto $dto): array
    {
        $uri = $this->normalizeMenuUri($dto->getUri(), $dto->getCode(), $dto->getParentId());
        $this->assertMenuPayload($dto->getName(), $dto->getCode(), $uri);
        $this->assertMenuUnique($dto->getCode(), $uri, 0);
        $parentPrefix = $this->resolveParentPrefix($dto->getParentId());

        $menu = $this->menuRepository->insert([
            'app_id' => StaffApp::appId(),
            'name' => $dto->getName(),
            'code' => $dto->getCode(),
            'uri' => $uri,
            'icon' => $dto->getIcon(),
            'parent_id' => $dto->getParentId(),
            'parent_prefix' => $parentPrefix,
            'sort' => $dto->getSort(),
            'status' => StaffApp::MENU_STATUS_ENABLED,
        ]);

        return $menu->getAttributes();
    }

    /**
     * @return array<string, mixed>
     */
    public function updateMenu(UpdateMenuDto $dto): array
    {
        $menu = $this->requireMenu($dto->getId());
        $uri = $this->normalizeMenuUri($dto->getUri(), $dto->getCode(), $dto->getParentId());
        $this->assertMenuPayload($dto->getName(), $dto->getCode(), $uri);
        $this->assertMenuUnique($dto->getCode(), $uri, $dto->getId());
        if ($dto->getParentId() === $dto->getId()) {
            throw StaffException::throw('父菜单不能是自身', -1);
        }
        $parentPrefix = $this->resolveParentPrefix($dto->getParentId());

        $this->menuRepository->save($menu->setData([
            'name' => $dto->getName(),
            'code' => $dto->getCode(),
            'uri' => $uri,
            'icon' => $dto->getIcon(),
            'parent_id' => $dto->getParentId(),
            'parent_prefix' => $parentPrefix,
            'sort' => $dto->getSort(),
        ]));

        return $menu->getAttributes();
    }

    public function switchMenuStatus(SwitchMenuStatusDto $dto): SwitchMenuStatusDto
    {
        $menu = $this->requireMenu($dto->getId());
        $status = $dto->getStatus() === StaffApp::MENU_STATUS_ENABLED
            ? StaffApp::MENU_STATUS_ENABLED
            : StaffApp::MENU_STATUS_DISABLED;

        $this->menuRepository->save($menu->setData(['status' => $status]));

        return SwitchMenuStatusDto::of((int) $menu->id, $status);
    }

    /**
     * @return array<int, int>
     */
    public function sortMenus(SortMenusDto $dto): array
    {
        $parentId = $dto->getParentId();
        $ids = array_values(array_filter($dto->getIds(), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            throw StaffException::throw('排序列表不能为空', -1);
        }

        $rows = $this->menuRepository->listSiblingRows($parentId);
        $siblingIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        sort($siblingIds);

        foreach ($ids as $id) {
            if (!in_array($id, $siblingIds, true)) {
                throw StaffException::throw('存在无效的菜单 ID', -1);
            }
        }

        $checkIds = $ids;
        sort($checkIds);
        if ($siblingIds !== $checkIds) {
            throw StaffException::throw('排序需包含全部同级菜单', -1);
        }

        $count = count($ids);
        foreach ($ids as $index => $id) {
            $menu = $this->requireMenu($id);
            $this->menuRepository->save($menu->setData(['sort' => $count - $index]));
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMenu(MenuIdDto $dto): array
    {
        return $this->requireMenu($dto->getId())->getAttributes();
    }

    public function deleteMenu(MenuIdDto $dto): int
    {
        $menu = $this->requireMenu($dto->getId());
        if ($this->menuRepository->hasVisibleChild((int) $menu->id)) {
            throw StaffException::throw('请先删除子菜单', -1);
        }

        $this->menuRepository->save($menu->setData([
            'status' => StaffApp::MENU_STATUS_DELETED,
            'delete_at' => date('Y-m-d H:i:s'),
        ]));
        $this->rolePageRepository->deleteByPageId((int) $menu->id);

        return (int) $menu->id;
    }

    /**
     * 首次注册时补齐默认菜单与超级管理员角色。
     */
    public function ensureBootstrap(): StaffRoleEntity
    {
        $this->ensureDefaultMenus();
        $this->reconcileNavMenus();
        $this->ensureSystemRole(
            StaffRoleCode::SUPER_ADMIN,
            '超级管理员',
            '拥有系统全部权限',
            true,
        );
        $this->ensureSystemRole(
            StaffRoleCode::EDITOR_TASK_GROUP,
            '任务编辑组',
            '可编辑计划任务（含他人创建的任务），适用于同事离职后的任务维护',
            false,
        );

        $super = $this->roleRepository->findByCode(StaffRoleCode::SUPER_ADMIN);
        if ($super === null) {
            throw StaffException::throw('超级管理员角色初始化失败', -1);
        }

        return $super;
    }

    private function ensureSystemRole(string $code, string $name, string $desc, bool $isSuper): StaffRoleEntity
    {
        $role = $this->roleRepository->findByCode($code);
        if ($role) {
            return $role;
        }

        return $this->roleRepository->insert([
            'app_id' => StaffApp::appId(),
            'name' => $name,
            'code' => $code,
            'desc' => $desc,
            'status' => 1,
            'is_super_role' => $isSuper ? 1 : 0,
        ]);
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function rolesGroupedByUserIds(array $userIds): array
    {
        $grouped = [];
        if ($userIds === []) {
            return $grouped;
        }

        $rels = $this->userRoleRepository->listRowsByUserIds($userIds);
        $roleIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['role_id'], $rels)));
        $roles = [];
        if ($roleIds !== []) {
            foreach ($this->roleRepository->listEnabledRowsByIds($roleIds) as $row) {
                $roles[(int) $row['id']] = [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                    'code' => (string) $row['code'],
                    'isSuperRole' => (int) ($row['is_super_role'] ?? 0) === 1,
                ];
            }
        }
        foreach ($rels as $rel) {
            $userId = (int) $rel['user_id'];
            $roleId = (int) $rel['role_id'];
            if (!isset($roles[$roleId])) {
                continue;
            }
            $grouped[$userId][] = $roles[$roleId];
        }

        return $grouped;
    }

    /**
     * @param array<int, int> $roleIds
     */
    public function assertRolesExist(array $roleIds): void
    {
        if ($roleIds === []) {
            return;
        }
        if ($this->roleRepository->countExistingForApp($roleIds) !== count(array_unique($roleIds))) {
            throw StaffException::throw('角色不存在或已失效', -1);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menusForUser(int $userId, bool $isSuper): array
    {
        $all = $this->loadMenuRows(StaffApp::MENU_STATUS_ENABLED);
        if ($isSuper) {
            return $this->menuDtosToArray($this->buildMenuTree($all));
        }

        $roleIds = array_map(
            static fn (array $row): int => (int) $row['role_id'],
            $this->userRoleRepository->listRowsByUserId($userId),
        );
        $roleIds = $this->enabledRoleIds($roleIds);
        if ($roleIds === []) {
            return [];
        }

        $pageIds = array_map(
            static fn (array $row): int => (int) $row['page_id'],
            $this->rolePageRepository->listRowsByRoleIds($roleIds),
        );
        $byId = [];
        foreach ($all as $row) {
            $byId[(int) $row['id']] = $row;
        }
        $pageIdSet = [];
        foreach (array_unique($pageIds) as $pageId) {
            if ($pageId <= 0) {
                continue;
            }
            $pageIdSet[$pageId] = true;
            $current = $pageId;
            while (isset($byId[$current])) {
                $parentId = (int) ($byId[$current]['parent_id'] ?? 0);
                if ($parentId <= 0 || isset($pageIdSet[$parentId])) {
                    break;
                }
                $pageIdSet[$parentId] = true;
                $current = $parentId;
            }
        }
        $allowed = array_values(array_filter($all, static fn (array $row): bool => isset($pageIdSet[(int) $row['id']])));

        return $this->menuDtosToArray($this->buildMenuTree($allowed));
    }

    /**
     * @param array<int, int> $pageIds
     */
    public function replaceRolePages(int $roleId, array $pageIds): void
    {
        $this->rolePageRepository->replaceForRole($roleId, $pageIds);
    }

    /**
     * @param array<int, int> $apiPerIds
     * @param array<int, int> $taskPerIds
     */
    public function replaceRolePermissions(int $roleId, array $apiPerIds, array $taskPerIds): void
    {
        $this->rolePermissionRepository->replaceForRole($roleId, $apiPerIds, $taskPerIds);
    }

    private function requireRole(int $id): StaffRoleEntity
    {
        if ($id <= 0) {
            throw StaffException::throw('id不能为空', -1);
        }
        $role = $this->roleRepository->findById($id);
        if (!$role || (int) $role->app_id !== StaffApp::appId()) {
            throw StaffException::throw('角色不存在', -1);
        }

        return $role;
    }

    private function requireMenu(int $id): StaffMenuPageEntity
    {
        if ($id <= 0) {
            throw StaffException::throw('id不能为空', -1);
        }
        $menu = $this->menuRepository->findById($id);
        if (!$menu || (int) $menu->status === StaffApp::MENU_STATUS_DELETED) {
            throw StaffException::throw('菜单不存在', -1);
        }

        return $menu;
    }

    private function normalizeMenuUri(string $uri, string $code, int $parentId): string
    {
        $uri = trim($uri);
        if ($uri !== '') {
            return $uri;
        }
        if ($parentId <= 0 && $code !== '') {
            return '/' . ltrim($code, '/');
        }

        return '';
    }

    private function assertMenuPayload(string $name, string $code, string $uri): void
    {
        if ($name === '' || $code === '') {
            throw StaffException::throw('菜单名称和标识不能为空', -1);
        }
        if ($uri === '') {
            throw StaffException::throw('菜单 URI 不能为空', -1);
        }
    }

    private function assertMenuUnique(string $code, string $uri, int $exceptId): void
    {
        if ($this->menuRepository->existsVisibleCode($code, $exceptId)) {
            throw StaffException::throw('菜单标识已存在', -1);
        }
        if ($this->menuRepository->existsVisibleUriForApp($uri, $exceptId)) {
            throw StaffException::throw('菜单 URI 已存在', -1);
        }
    }

    private function resolveParentPrefix(int $parentId): string
    {
        if ($parentId <= 0) {
            return '';
        }
        $parent = $this->requireMenu($parentId);
        $prefix = trim((string) $parent->parent_prefix, ',');
        $ids = $prefix === '' ? [] : explode(',', $prefix);
        $ids[] = (string) $parent->id;

        return implode(',', $ids);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadMenuRows(?int $status = null): array
    {
        return $this->menuRepository->listVisibleRows($status);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, StaffMenuRowDto>
     */
    private function buildMenuTree(array $rows): array
    {
        /** @var array<int, StaffMenuRowDto> $map */
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = StaffMenuRowDto::fromEntityRow($row);
        }

        $roots = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = (int) ($row['parent_id'] ?? 0);
            if ($parentId > 0 && isset($map[$parentId])) {
                $map[$parentId]->addChild($map[$id]);
            } else {
                $roots[] = $map[$id];
            }
        }

        return $roots;
    }

    /**
     * @param array<int, StaffMenuRowDto> $dtos
     * @return array<int, array<string, mixed>>
     */
    private function menuDtosToArray(array $dtos): array
    {
        $out = [];
        foreach ($dtos as $dto) {
            $out[] = $dto->toDeepArray();
        }

        return $out;
    }

    /**
     * @return array<int, int>
     */
    private function pageIdsOfRole(int $roleId): array
    {
        return $this->rolePageRepository->listPageIdsByRoleId($roleId);
    }

    /**
     * @return array<int, int>
     */
    private function permissionIdsOfRole(int $roleId, int $type): array
    {
        return $this->rolePermissionRepository->listPermissionIdsByRoleAndType($roleId, $type);
    }

    /**
     * @return array<int, int>
     */
    public function enabledRoleIdsForUser(int $userId): array
    {
        $roleIds = array_map(
            static fn (array $row): int => (int) $row['role_id'],
            $this->userRoleRepository->listRowsByUserId($userId),
        );

        return $this->enabledRoleIds($roleIds);
    }

    /**
     * @param array<int, int> $roleIds
     * @return array<int, int>
     */
    private function enabledRoleIds(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }
        return array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $this->roleRepository->listEnabledRowsByIds($roleIds),
        ));
    }

    /**
     * @param array<int, int> $roleIds
     * @return array<int, int>
     */
    private function countUsersByRoleIds(array $roleIds): array
    {
        return $this->userRoleRepository->countUsersGroupedByRoleIds($roleIds);
    }

    /**
     * @param array<int, int> $roleIds
     * @return array<int, int>
     */
    private function countMenusByRoleIds(array $roleIds): array
    {
        return $this->rolePageRepository->countPagesGroupedByRoleIds($roleIds);
    }

    /**
     * 侧栏菜单分组与条目；不含创建任务等页面节点。
     *
     * @return array<int, array{name: string, code: string, uri: string, icon: string, sort: int, parent: string}>
     */
    private static function defaultNavMenus(): array
    {
        return [
            ['name' => 'Cron 管理', 'code' => 'cron', 'uri' => '/cron', 'icon' => '', 'sort' => 100, 'parent' => ''],
            ['name' => 'Dashboard', 'code' => 'cron:dashboard', 'uri' => '/dashboard', 'icon' => 'el-icon-data-line', 'sort' => 50, 'parent' => 'cron'],
            ['name' => '计划任务', 'code' => 'cron:tasks', 'uri' => '/tasks', 'icon' => 'el-icon-s-order', 'sort' => 40, 'parent' => 'cron'],
            ['name' => '执行记录', 'code' => 'cron:executions', 'uri' => '/executions', 'icon' => 'el-icon-time', 'sort' => 30, 'parent' => 'cron'],
            ['name' => 'Cron Nodes', 'code' => 'cron:nodes', 'uri' => '/nodes', 'icon' => 'el-icon-monitor', 'sort' => 20, 'parent' => 'cron'],
            ['name' => 'Runtime', 'code' => 'cron:runtime', 'uri' => '/runtime', 'icon' => 'el-icon-odometer', 'sort' => 10, 'parent' => 'cron'],
            ['name' => '权限管理', 'code' => 'auth', 'uri' => '/auth', 'icon' => '', 'sort' => 90, 'parent' => ''],
            ['name' => '用户管理', 'code' => 'auth:users', 'uri' => '/users', 'icon' => 'el-icon-user', 'sort' => 30, 'parent' => 'auth'],
            ['name' => '角色管理', 'code' => 'auth:roles', 'uri' => '/roles', 'icon' => 'el-icon-s-custom', 'sort' => 20, 'parent' => 'auth'],
            ['name' => '菜单管理', 'code' => 'auth:menus', 'uri' => '/menus', 'icon' => 'el-icon-menu', 'sort' => 10, 'parent' => 'auth'],
        ];
    }

    /**
     * 页面节点（非侧栏菜单），不参与菜单关联与列表展示。
     *
     * @return array<int, string>
     */
    private static function retiredPageMenuCodes(): array
    {
        return [
            'cron:tasks:create',
            'cron:executions:log',
        ];
    }

    private function ensureDefaultMenus(): void
    {
        if ($this->menuRepository->hasAnyForApp()) {
            return;
        }

        $defs = self::defaultNavMenus();

        $codeIds = [];
        foreach ($defs as $def) {
            $parentId = 0;
            $parentPrefix = '';
            if ($def['parent'] !== '' && isset($codeIds[$def['parent']])) {
                $parentId = $codeIds[$def['parent']];
                $parent = $this->menuRepository->findById($parentId);
                if ($parent) {
                    $prefix = trim((string) $parent->parent_prefix, ',');
                    $ids = $prefix === '' ? [] : explode(',', $prefix);
                    $ids[] = (string) $parent->id;
                    $parentPrefix = implode(',', $ids);
                }
            }
            $menu = $this->menuRepository->insert([
                'app_id' => StaffApp::appId(),
                'name' => $def['name'],
                'code' => $def['code'],
                'uri' => $def['uri'],
                'icon' => $def['icon'],
                'parent_id' => $parentId,
                'parent_prefix' => $parentPrefix,
                'sort' => $def['sort'],
                'status' => StaffApp::MENU_STATUS_ENABLED,
            ]);
            $codeIds[$def['code']] = (int) $menu->id;
        }
    }

    /**
     * 去掉页面节点，并把 Cron 条目归到「Cron 管理」分组下。
     */
    private function reconcileNavMenus(): void
    {
        foreach (self::retiredPageMenuCodes() as $code) {
            $page = $this->findVisibleByCode($code);
            if (!$page) {
                continue;
            }
            $this->menuRepository->save($page->setData([
                'status' => StaffApp::MENU_STATUS_DELETED,
                'delete_at' => date('Y-m-d H:i:s'),
            ]));
            $this->rolePageRepository->deleteByPageId((int) $page->id);
        }

        $cronGroup = $this->findVisibleByCode('cron');
        if (!$cronGroup) {
            $cronGroup = $this->menuRepository->insert([
                'app_id' => StaffApp::appId(),
                'name' => 'Cron 管理',
                'code' => 'cron',
                'uri' => '/cron',
                'icon' => '',
                'parent_id' => 0,
                'parent_prefix' => '',
                'sort' => 100,
                'status' => StaffApp::MENU_STATUS_ENABLED,
            ]);
        }

        $cronGroupId = (int) $cronGroup->id;
        $cronPrefix = (string) $cronGroupId;
        foreach (['cron:dashboard', 'cron:tasks', 'cron:executions', 'cron:nodes', 'cron:runtime'] as $code) {
            $item = $this->findVisibleByCode($code);
            if (!$item || (int) $item->parent_id === $cronGroupId) {
                continue;
            }
            $this->menuRepository->save($item->setData([
                'parent_id' => $cronGroupId,
                'parent_prefix' => $cronPrefix,
            ]));
        }
    }

    private function findVisibleByCode(string $code): ?StaffMenuPageEntity
    {
        return $this->menuRepository->findVisibleByCode($code);
    }

    private function isStatusLockedRole(StaffRoleEntity $role): bool
    {
        return $role->isSuperRole() || StaffRoleCode::isStatusLocked((string) $role->code);
    }
}
