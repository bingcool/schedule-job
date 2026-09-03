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
use App\Module\Staff\Entity\StaffRolePageEntity;
use App\Module\Staff\Entity\StaffRolePermissionEntity;
use App\Module\Staff\Entity\StaffUserRoleEntity;
use App\Module\Staff\Exception\StaffException;
use App\Module\Staff\Response\StaffManager\ListRolesPageResult;
use App\Module\Staff\StaffApp;
use App\Module\Staff\StaffRoleCode;

class StaffRoleService
{
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

        $qb = StaffRoleEntity::query()->where('app_id', $appId);
        if ($name !== '') {
            $qb->where('name', 'like', '%' . $name . '%');
        }
        if ($status !== null) {
            $qb->where('status', $status);
        }

        $pageResult = new ListRolesPageResult();
        $pageResult->setPage($query->getPage());
        $pageResult->setPageSize($query->getPageSize());
        $pageResult->setTotal((int) $qb->clone()->count());

        $rows = $qb->order('id', 'desc')->limit($query->getOffset(), $query->getPageSize())->select()->toArray();
        $roleIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $userCounts = $this->countUsersByRoleIds($roleIds);
        $menuCounts = $this->countMenusByRoleIds($roleIds);

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
        $appId = StaffApp::appId();
        $roles = StaffRoleEntity::query()->where('app_id', $appId)->select()->toArray();
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
        $userCount = (int) StaffUserRoleEntity::query()->where('app_id', $appId)->count();

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
        $rows = StaffRoleEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('status', 1)
            ->order('id', 'asc')
            ->select()
            ->toArray();

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
        if ((new StaffRoleEntity())->loadByCode($code)) {
            throw StaffException::throw('角色标识已存在', -1);
        }
        if (StaffRoleCode::isSystem($code)) {
            throw StaffException::throw('不能使用系统保留的角色标识', -1);
        }

        $role = new StaffRoleEntity();
        $role->setData([
            'app_id' => StaffApp::appId(),
            'name' => $name,
            'code' => $code,
            'desc' => $dto->getDesc(),
            'status' => $dto->getStatus(),
            'is_super_role' => 0,
        ]);
        $role->save();

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

        $role->setData([
            'name' => $dto->getName(),
            'desc' => $dto->getDesc(),
            'status' => $role->isSuperRole() ? 1 : $dto->getStatus(),
        ]);
        $role->save();

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
            $rows = StaffMenuPageEntity::queryVisible()
                ->where('app_id', StaffApp::appId())
                ->whereIn('id', $pageIds)
                ->select()
                ->toArray();
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

        StaffRolePageEntity::query()->where('role_id', (int) $role->id)->delete();
        StaffRolePermissionEntity::query()->where('role_id', (int) $role->id)->delete();
        $role->delete();

        return (int) $role->id;
    }

    public function switchStatus(SwitchRoleStatusDto $dto): SwitchRoleStatusDto
    {
        $role = $this->requireRole($dto->getId());
        $status = $dto->getStatus();
        if ($status === 0 && $role->isSuperRole()) {
            throw StaffException::throw('超级管理员角色不能禁用', -1);
        }

        $role->setData(['status' => $status]);
        $role->save();

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

        $menu = new StaffMenuPageEntity();
        $menu->setData([
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
        $menu->save();

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

        $menu->setData([
            'name' => $dto->getName(),
            'code' => $dto->getCode(),
            'uri' => $uri,
            'icon' => $dto->getIcon(),
            'parent_id' => $dto->getParentId(),
            'parent_prefix' => $parentPrefix,
            'sort' => $dto->getSort(),
        ]);
        $menu->save();

        return $menu->getAttributes();
    }

    public function switchMenuStatus(SwitchMenuStatusDto $dto): SwitchMenuStatusDto
    {
        $menu = $this->requireMenu($dto->getId());
        $status = $dto->getStatus() === StaffApp::MENU_STATUS_ENABLED
            ? StaffApp::MENU_STATUS_ENABLED
            : StaffApp::MENU_STATUS_DISABLED;

        $menu->setData(['status' => $status]);
        $menu->save();

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

        $rows = StaffMenuPageEntity::queryVisible()
            ->where('app_id', StaffApp::appId())
            ->where('parent_id', $parentId)
            ->select()
            ->toArray();
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
            $menu->setData(['sort' => $count - $index]);
            $menu->save();
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
        $child = StaffMenuPageEntity::queryVisible()->where('parent_id', (int) $menu->id)->find();
        if ($child) {
            throw StaffException::throw('请先删除子菜单', -1);
        }

        $menu->setData([
            'status' => StaffApp::MENU_STATUS_DELETED,
            'delete_at' => date('Y-m-d H:i:s'),
        ]);
        $menu->save();
        StaffRolePageEntity::query()->where('page_id', (int) $menu->id)->delete();

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

        return (new StaffRoleEntity())->loadByCode(StaffRoleCode::SUPER_ADMIN);
    }

    private function ensureSystemRole(string $code, string $name, string $desc, bool $isSuper): StaffRoleEntity
    {
        $role = (new StaffRoleEntity())->loadByCode($code);
        if ($role) {
            return $role;
        }

        $role = new StaffRoleEntity();
        $role->setData([
            'app_id' => StaffApp::appId(),
            'name' => $name,
            'code' => $code,
            'desc' => $desc,
            'status' => 1,
            'is_super_role' => $isSuper ? 1 : 0,
        ]);
        $role->save();

        return $role;
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

        $rels = StaffUserRoleEntity::query()
            ->where('app_id', StaffApp::appId())
            ->whereIn('user_id', $userIds)
            ->select()
            ->toArray();
        $roleIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['role_id'], $rels)));
        $roles = [];
        if ($roleIds !== []) {
            foreach (StaffRoleEntity::query()->whereIn('id', $roleIds)->where('status', 1)->select()->toArray() as $row) {
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
        $rows = StaffRoleEntity::query()->whereIn('id', $roleIds)->where('app_id', StaffApp::appId())->select()->toArray();
        if (count($rows) !== count(array_unique($roleIds))) {
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
            StaffUserRoleEntity::query()->where('app_id', StaffApp::appId())->where('user_id', $userId)->select()->toArray()
        );
        $roleIds = $this->enabledRoleIds($roleIds);
        if ($roleIds === []) {
            return [];
        }

        $pageIds = array_map(
            static fn (array $row): int => (int) $row['page_id'],
            StaffRolePageEntity::query()->where('app_id', StaffApp::appId())->whereIn('role_id', $roleIds)->select()->toArray()
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
        $appId = StaffApp::appId();
        StaffRolePageEntity::query()->where('app_id', $appId)->where('role_id', $roleId)->delete();

        foreach (array_unique($pageIds) as $pageId) {
            if ($pageId <= 0) {
                continue;
            }
            $rel = new StaffRolePageEntity();
            $rel->setData([
                'app_id' => $appId,
                'role_id' => $roleId,
                'page_id' => $pageId,
            ]);
            $rel->save();
        }
    }

    /**
     * @param array<int, int> $apiPerIds
     * @param array<int, int> $taskPerIds
     */
    public function replaceRolePermissions(int $roleId, array $apiPerIds, array $taskPerIds): void
    {
        $appId = StaffApp::appId();
        StaffRolePermissionEntity::query()->where('app_id', $appId)->where('role_id', $roleId)->delete();

        $this->insertPermissions($roleId, StaffApp::PERMISSION_TYPE_API, $apiPerIds);
        $this->insertPermissions($roleId, StaffApp::PERMISSION_TYPE_TASK, $taskPerIds);
    }

    /**
     * @param array<int, int> $perIds
     */
    private function insertPermissions(int $roleId, int $type, array $perIds): void
    {
        $appId = StaffApp::appId();
        foreach (array_unique($perIds) as $perId) {
            if ($perId <= 0) {
                continue;
            }
            $rel = new StaffRolePermissionEntity();
            $rel->setData([
                'app_id' => $appId,
                'type' => $type,
                'role_id' => $roleId,
                'per_id' => $perId,
            ]);
            $rel->save();
        }
    }

    private function requireRole(int $id): StaffRoleEntity
    {
        if ($id <= 0) {
            throw StaffException::throw('id不能为空', -1);
        }
        $role = (new StaffRoleEntity())->loadById($id);
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
        $menu = (new StaffMenuPageEntity())->loadById($id);
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
        $appId = StaffApp::appId();
        $codeQb = StaffMenuPageEntity::queryVisible()->where('code', $code);
        $uriQb = StaffMenuPageEntity::queryVisible()->where('uri', $uri)->where('app_id', $appId);
        if ($exceptId > 0) {
            $codeQb->where('id', '<>', $exceptId);
            $uriQb->where('id', '<>', $exceptId);
        }
        if ($codeQb->find()) {
            throw StaffException::throw('菜单标识已存在', -1);
        }
        if ($uriQb->find()) {
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
        $qb = StaffMenuPageEntity::queryVisible()->where('app_id', StaffApp::appId());
        if ($status !== null) {
            $qb->where('status', $status);
        }

        return $qb->order('sort', 'desc')->order('id', 'asc')->select()->toArray();
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
        $rows = StaffRolePageEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('role_id', $roleId)
            ->select()
            ->toArray();

        return array_values(array_map(static fn (array $row): int => (int) $row['page_id'], $rows));
    }

    /**
     * @return array<int, int>
     */
    private function permissionIdsOfRole(int $roleId, int $type): array
    {
        $rows = StaffRolePermissionEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('role_id', $roleId)
            ->where('type', $type)
            ->select()
            ->toArray();

        return array_values(array_map(static fn (array $row): int => (int) $row['per_id'], $rows));
    }

    /**
     * @return array<int, int>
     */
    public function enabledRoleIdsForUser(int $userId): array
    {
        $roleIds = array_map(
            static fn (array $row): int => (int) $row['role_id'],
            StaffUserRoleEntity::query()->where('app_id', StaffApp::appId())->where('user_id', $userId)->select()->toArray()
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
        $rows = StaffRoleEntity::query()
            ->where('app_id', StaffApp::appId())
            ->whereIn('id', $roleIds)
            ->where('status', 1)
            ->select()
            ->toArray();

        return array_values(array_map(static fn (array $row): int => (int) $row['id'], $rows));
    }

    /**
     * @param array<int, int> $roleIds
     * @return array<int, int>
     */
    private function countUsersByRoleIds(array $roleIds): array
    {
        $counts = [];
        if ($roleIds === []) {
            return $counts;
        }
        $rows = StaffUserRoleEntity::query()
            ->where('app_id', StaffApp::appId())
            ->whereIn('role_id', $roleIds)
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $roleId = (int) $row['role_id'];
            $counts[$roleId] = ($counts[$roleId] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param array<int, int> $roleIds
     * @return array<int, int>
     */
    private function countMenusByRoleIds(array $roleIds): array
    {
        $counts = [];
        if ($roleIds === []) {
            return $counts;
        }
        $rows = StaffRolePageEntity::query()
            ->where('app_id', StaffApp::appId())
            ->whereIn('role_id', $roleIds)
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $roleId = (int) $row['role_id'];
            $counts[$roleId] = ($counts[$roleId] ?? 0) + 1;
        }

        return $counts;
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
        $exists = StaffMenuPageEntity::queryVisible()->where('app_id', StaffApp::appId())->find();
        if ($exists) {
            return;
        }

        $defs = self::defaultNavMenus();

        $codeIds = [];
        foreach ($defs as $def) {
            $parentId = 0;
            $parentPrefix = '';
            if ($def['parent'] !== '' && isset($codeIds[$def['parent']])) {
                $parentId = $codeIds[$def['parent']];
                $parent = (new StaffMenuPageEntity())->loadById($parentId);
                if ($parent) {
                    $prefix = trim((string) $parent->parent_prefix, ',');
                    $ids = $prefix === '' ? [] : explode(',', $prefix);
                    $ids[] = (string) $parent->id;
                    $parentPrefix = implode(',', $ids);
                }
            }
            $menu = new StaffMenuPageEntity();
            $menu->setData([
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
            $menu->save();
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
            $page->setData([
                'status' => StaffApp::MENU_STATUS_DELETED,
                'delete_at' => date('Y-m-d H:i:s'),
            ]);
            $page->save();
            StaffRolePageEntity::query()->where('page_id', (int) $page->id)->delete();
        }

        $cronGroup = $this->findVisibleByCode('cron');
        if (!$cronGroup) {
            $cronGroup = new StaffMenuPageEntity();
            $cronGroup->setData([
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
            $cronGroup->save();
        }

        $cronGroupId = (int) $cronGroup->id;
        $cronPrefix = (string) $cronGroupId;
        foreach (['cron:dashboard', 'cron:tasks', 'cron:executions', 'cron:nodes', 'cron:runtime'] as $code) {
            $item = $this->findVisibleByCode($code);
            if (!$item || (int) $item->parent_id === $cronGroupId) {
                continue;
            }
            $item->setData([
                'parent_id' => $cronGroupId,
                'parent_prefix' => $cronPrefix,
            ]);
            $item->save();
        }
    }

    private function findVisibleByCode(string $code): ?StaffMenuPageEntity
    {
        $row = StaffMenuPageEntity::queryVisible()->where('code', $code)->find();
        if (!$row) {
            return null;
        }

        return (new StaffMenuPageEntity())->loadById((int) $row['id']);
    }
}
