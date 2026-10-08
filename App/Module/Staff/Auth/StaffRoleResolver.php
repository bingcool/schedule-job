<?php

declare(strict_types=1);

namespace App\Module\Staff\Auth;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffRoleBriefDto;
use App\Module\Staff\Repository\StaffUserRepository;
use App\Module\Staff\Service\StaffRoleService;
use Swoolefy\Support\Auth\RoleResolverInterface;
use Swoolefy\Support\Workflow\WorkflowHitlAuth;

/**
 * 当前登录用户的服务端角色源（组件 auth.role_resolver）。
 *
 * 只按 userId 查库，不读 JWT、不缓存到 Worker。
 * 已删除、已禁用或不存在的用户返回空数组，表示当前没有角色。
 * 超管在真实 roleCode 之外再带上 admin，供 HITL 的 isAdmin / allowed_roles 使用
 * （原先登录时写进 token 的同一别名）。
 */
final class StaffRoleResolver implements RoleResolverInterface
{
    public function currentRoles(string $userId): array
    {
        if (!preg_match('/^[1-9][0-9]*$/', $userId)) {
            return [];
        }

        $id = (int) $userId;
        $user = (new StaffUserRepository())->findById($id);
        if ($user === null || $user->isDeleted() || $user->isDisabled()) {
            return [];
        }

        $roles = (new StaffRoleService())->rolesGroupedByUserIds([$id])[$id] ?? [];

        return self::codesOf($roles);
    }

    /**
     * @param list<StaffRoleBriefDto> $roles
     * @return list<string>
     */
    private static function codesOf(array $roles): array
    {
        $codes = [];
        $isSuper = false;
        foreach ($roles as $role) {
            $code = trim($role->getCode());
            if ($code !== '') {
                $codes[$code] = $code;
            }
            if ($role->getIsSuperRole()) {
                $isSuper = true;
            }
        }
        if ($isSuper) {
            $codes[WorkflowHitlAuth::ADMIN_ROLE] = WorkflowHitlAuth::ADMIN_ROLE;
        }

        return array_values($codes);
    }
}
