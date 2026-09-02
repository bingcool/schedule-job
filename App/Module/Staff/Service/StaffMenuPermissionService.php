<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use App\Module\Staff\Entity\StaffMenuPageEntity;
use App\Module\Staff\Entity\StaffRolePageEntity;
use App\Module\Staff\StaffApp;
use Swoolefy\Support\Auth\AuthException;
use Swoolefy\Support\FrameworkContext;

/**
 * 按菜单页面权限（staff_role_page）校验管理端 API。
 * 暂不处理 staff_role_permission 中的 API / 任务细粒度权限。
 */
class StaffMenuPermissionService
{
    /** 分组占位 URI，本身不对应页面也不单独放行 API。 */
    private const GROUP_PLACEHOLDER_URIS = ['/cron', '/auth'];

    /** 登录用户自身相关接口，仅需登录。 */
    private const AUTH_SKIP_PREFIXES = [
        '/api/v1/auth/me',
        '/api/v1/auth/password',
        '/api/v1/auth/profile',
    ];

    /**
     * API 路径前缀 => 所需菜单 URI（满足任一即可）。
     * 按前缀长度降序匹配，更具体的路径写在前面。
     *
     * @var array<string, string|list<string>>
     */
    private const API_PATH_MENU_RULES = [
        '/api/v1/dashboard/overview' => '/dashboard',
        '/api/v1/dashboard/execution-trend' => '/dashboard',
        '/api/v1/tasks/logs' => '/executions',
        '/api/v1/tasks/execution' => '/executions',
        '/api/v1/tasks/stats' => ['/tasks', '/executions'],
        '/api/v1/tasks/expression/preview' => '/tasks',
        '/api/v1/tasks/batch-status' => '/tasks',
        '/api/v1/tasks/status' => '/tasks',
        '/api/v1/tasks/detail' => '/tasks',
        '/api/v1/tasks/duplicate' => '/tasks',
        '/api/v1/tasks/run' => '/tasks',
        '/api/v1/tasks' => '/tasks',
        '/api/v1/nodes/detail' => '/nodes',
        '/api/v1/nodes' => '/nodes',
        '/api/v1/node-groups/detail' => '/nodes',
        '/api/v1/node-groups' => ['/nodes', '/tasks', '/users'],
        '/api/v1/runtime/overview' => '/runtime',
        '/api/v1/users/status' => '/users',
        '/api/v1/users/roles' => '/users',
        '/api/v1/users/node-groups' => '/users',
        '/api/v1/users/detail' => '/users',
        '/api/v1/users' => '/users',
        '/api/v1/roles/stats' => '/roles',
        '/api/v1/roles/options' => '/users',
        '/api/v1/roles/status' => '/roles',
        '/api/v1/roles/detail' => '/roles',
        '/api/v1/roles' => '/roles',
        '/api/v1/menus/detail' => '/menus',
        '/api/v1/menus' => '/menus',
    ];

    private StaffRoleService $staffRoleService {
        get => $this->staffRoleService ??= new StaffRoleService();
    }

    public function __construct(?StaffRoleService $staffRoleService = null)
    {
        if ($staffRoleService !== null) {
            $this->staffRoleService = $staffRoleService;
        }
    }

    /**
     * 校验当前登录用户是否拥有访问该 API 所需的菜单页面权限。
     *
     * @throws AuthException
     */
    public function assertApiAllowed(string $path): void
    {
        $required = $this->resolveRequiredMenuUris($path);
        if ($required === null) {
            return;
        }

        $userId = (int) (FrameworkContext::getUserId() ?? 0);
        if ($userId <= 0) {
            throw new AuthException('Unauthenticated', 401);
        }
        if ($this->isSuperUser($userId)) {
            return;
        }

        $granted = $this->grantedMenuUris($userId);
        foreach ($required as $uri) {
            if (in_array($uri, $granted, true)) {
                return;
            }
        }

        throw new AuthException('无菜单页面权限', 403);
    }

    /**
     * @return list<string>|null null 表示无需菜单权限校验
     */
    public function resolveRequiredMenuUris(string $path): ?array
    {
        $path = $this->normalizeApiPath($path);
        foreach (self::AUTH_SKIP_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return null;
            }
        }

        $rules = self::API_PATH_MENU_RULES;
        uksort($rules, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($rules as $prefix => $menuUri) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return is_array($menuUri) ? $menuUri : [$menuUri];
            }
        }

        if (str_starts_with($path, '/api/v1/')) {
            throw new AuthException('未配置菜单页面权限映射', 403);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function grantedMenuUris(int $userId): array
    {
        $roleIds = $this->staffRoleService->enabledRoleIdsForUser($userId);
        if ($roleIds === []) {
            return [];
        }

        $pageIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['page_id'],
            StaffRolePageEntity::query()->where('app_id', StaffApp::appId())->whereIn('role_id', $roleIds)->select()->toArray()
        )));
        if ($pageIds === []) {
            return [];
        }

        $rows = StaffMenuPageEntity::queryVisible()
            ->where('app_id', StaffApp::appId())
            ->whereIn('id', $pageIds)
            ->where('status', StaffApp::MENU_STATUS_ENABLED)
            ->select()
            ->toArray();

        $uris = [];
        foreach ($rows as $row) {
            $uri = $this->normalizeMenuUri((string) ($row['uri'] ?? ''));
            if ($uri === '' || in_array($uri, self::GROUP_PLACEHOLDER_URIS, true)) {
                continue;
            }
            $uris[] = $uri;
        }

        return array_values(array_unique($uris));
    }

    public function isSuperUser(int $userId): bool
    {
        $roles = $this->staffRoleService->rolesGroupedByUserIds([$userId])[$userId] ?? [];
        foreach ($roles as $role) {
            if (!empty($role['isSuperRole'])) {
                return true;
            }
        }

        return false;
    }

    private function normalizeApiPath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }

    private function normalizeMenuUri(string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '') {
            return '';
        }
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return rtrim($uri, '/') ?: '/';
    }
}
