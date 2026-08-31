<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use App\Module\Cron\Entity\CronAgentNodeGroupEntity;
use App\Module\Staff\Dto\StaffManager\CreateUserDto;
use App\Module\Staff\Dto\StaffManager\ListUsersQueryDto;
use App\Module\Staff\Dto\StaffManager\StaffUserRowDto;
use App\Module\Staff\Dto\StaffManager\SwitchUserStatusDto;
use App\Module\Staff\Dto\StaffManager\UpdateUserDto;
use App\Module\Staff\Dto\StaffManager\UserIdDto;
use App\Module\Staff\Entity\StaffUserEntity;
use App\Module\Staff\Entity\StaffUserRelateNodeGroupEntity;
use App\Module\Staff\Entity\StaffUserRoleEntity;
use App\Module\Staff\Exception\StaffException;
use App\Module\Staff\Response\StaffManager\ListUsersPageResult;
use App\Module\Staff\StaffApp;
use Swoolefy\Support\FrameworkContext;

class StaffUserService
{
    private StaffRoleService $staffRoleService {
        get => $this->staffRoleService ??= new StaffRoleService();
        set => $this->staffRoleService = $value;
    }

    public function __construct(?StaffRoleService $staffRoleService = null)
    {
        if ($staffRoleService !== null) {
            $this->staffRoleService = $staffRoleService;
        }
    }

    public function listUsers(ListUsersQueryDto $query): ListUsersPageResult
    {
        $account = trim((string) ($query->getAccount() ?? ''));
        $userName = trim((string) ($query->getUserName() ?? ''));
        $status = $query->getStatus();

        $qb = StaffUserEntity::query();
        if ($account !== '') {
            $qb->where('account', 'like', '%' . $account . '%');
        }
        if ($userName !== '') {
            $qb->where('user_name', 'like', '%' . $userName . '%');
        }
        if ($status === 1) {
            $qb->whereNull('delete_at');
        } elseif ($status === 0) {
            $qb->whereNotNull('delete_at');
        }

        $pageResult = new ListUsersPageResult();
        $pageResult->setPage($query->getPage());
        $pageResult->setPageSize($query->getPageSize());
        $pageResult->setTotal((int) $qb->clone()->count());

        $rows = $qb->order('id', 'desc')->limit($query->getOffset(), $query->getPageSize())->select()->toArray();
        $userIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $rolesMap = $this->staffRoleService->rolesGroupedByUserIds($userIds);
        $groupsMap = $this->nodeGroupIdsGroupedByUserIds($userIds);

        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $roles = $rolesMap[$userId] ?? [];
            $row['roles'] = $roles;
            $row['role_ids'] = array_map(static fn (array $role): int => (int) $role['id'], $roles);
            $row['node_group_ids'] = $groupsMap[$userId] ?? [];
            $row['is_super'] = $this->hasSuperRole($roles);
            $pageResult->addListItem(StaffUserRowDto::fromEntityRow($row));
        }

        return $pageResult;
    }

    /**
     * @return array<string, mixed>
     */
    public function createUser(CreateUserDto $dto): array
    {
        $this->assertAccountAndName($dto->getAccount(), $dto->getUserName());
        $this->assertPassword($dto->getPassword());
        if ((new StaffUserEntity())->loadByAccount($dto->getAccount())) {
            throw StaffException::throw('账号已存在', -1);
        }
        $this->staffRoleService->assertRolesExist($dto->getRoleIds());
        $this->assertNodeGroupsExist($dto->getNodeGroupIds());

        $user = new StaffUserEntity();
        $user->setData([
            'account' => $dto->getAccount(),
            'user_name' => $dto->getUserName(),
            'password' => self::hashPassword($dto->getPassword()),
        ]);
        $user->save();

        $this->replaceUserRoles((int) $user->id, $dto->getRoleIds());
        $this->replaceUserNodeGroups((int) $user->id, $dto->getNodeGroupIds());

        return $this->getUser(UserIdDto::of((int) $user->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function updateUser(UpdateUserDto $dto): array
    {
        $user = $this->requireUser($dto->getId());
        $this->assertAccountAndName($dto->getAccount(), $dto->getUserName());
        $exist = StaffUserEntity::query()->where('account', $dto->getAccount())->where('id', '<>', $dto->getId())->find();
        if ($exist) {
            throw StaffException::throw('账号已存在', -1);
        }
        $this->staffRoleService->assertRolesExist($dto->getRoleIds());
        $this->assertNodeGroupsExist($dto->getNodeGroupIds());

        $data = [
            'account' => $dto->getAccount(),
            'user_name' => $dto->getUserName(),
        ];
        if ($dto->getPassword() !== '') {
            $this->assertPassword($dto->getPassword());
            $data['password'] = self::hashPassword($dto->getPassword());
        }
        $user->setData($data);
        $user->save();

        $this->replaceUserRoles((int) $user->id, $dto->getRoleIds());
        $this->replaceUserNodeGroups((int) $user->id, $dto->getNodeGroupIds());

        return $this->getUser(UserIdDto::of((int) $user->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function getUser(UserIdDto $dto): array
    {
        $user = $this->requireUser($dto->getId());
        $attrs = $user->getAttributes();
        unset($attrs['password']);
        $roles = $this->staffRoleService->rolesGroupedByUserIds([(int) $user->id])[(int) $user->id] ?? [];
        $attrs['roles'] = $roles;
        $attrs['role_ids'] = array_map(static fn (array $role): int => (int) $role['id'], $roles);
        $attrs['node_group_ids'] = $this->nodeGroupIdsGroupedByUserIds([(int) $user->id])[(int) $user->id] ?? [];
        $attrs['is_super'] = $this->hasSuperRole($roles);

        return $attrs;
    }

    public function deleteUser(UserIdDto $dto): int
    {
        return $this->switchStatus(SwitchUserStatusDto::of($dto->getId(), 0))->getId();
    }

    public function switchStatus(SwitchUserStatusDto $dto): SwitchUserStatusDto
    {
        $user = $this->requireUser($dto->getId());
        $this->assertNotSelf((int) $user->id, '不能禁用当前登录账号');

        if ($dto->getStatus() === 1) {
            $user->setData([
                'delete_at' => null,
                'enabled_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $user->setData([
                'delete_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $user->save();

        return SwitchUserStatusDto::of((int) $user->id, $dto->getStatus());
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function assertPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw StaffException::throw('密码至少 8 位', -1);
        }
    }

    public function requireUser(int $id): StaffUserEntity
    {
        if ($id <= 0) {
            throw StaffException::throw('id不能为空', -1);
        }
        $user = (new StaffUserEntity())->loadById($id);
        if (!$user) {
            throw StaffException::throw('用户不存在', -1);
        }

        return $user;
    }

    /**
     * @param array<int, int> $roleIds
     */
    public function replaceUserRoles(int $userId, array $roleIds): void
    {
        $appId = StaffApp::appId();
        StaffUserRoleEntity::query()->where('app_id', $appId)->where('user_id', $userId)->delete();
        foreach (array_unique($roleIds) as $roleId) {
            if ($roleId <= 0) {
                continue;
            }
            $rel = new StaffUserRoleEntity();
            $rel->setData([
                'app_id' => $appId,
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
            $rel->save();
        }
    }

    /**
     * @param array<int, int> $groupIds
     */
    public function replaceUserNodeGroups(int $userId, array $groupIds): void
    {
        StaffUserRelateNodeGroupEntity::query()->where('user_id', $userId)->delete();
        foreach (array_unique($groupIds) as $groupId) {
            if ($groupId <= 0) {
                continue;
            }
            $rel = new StaffUserRelateNodeGroupEntity();
            $rel->setData([
                'user_id' => $userId,
                'node_group_id' => $groupId,
            ]);
            $rel->save();
        }
    }

    private function assertAccountAndName(string $account, string $userName): void
    {
        if ($account === '' || $userName === '') {
            throw StaffException::throw('账号和用户名称不能为空', -1);
        }
    }

    /**
     * @param array<int, int> $groupIds
     */
    private function assertNodeGroupsExist(array $groupIds): void
    {
        if ($groupIds === []) {
            return;
        }
        $rows = CronAgentNodeGroupEntity::query()->whereIn('id', $groupIds)->select()->toArray();
        if (count($rows) !== count(array_unique($groupIds))) {
            throw StaffException::throw('节点组不存在', -1);
        }
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, array<int, int>>
     */
    private function nodeGroupIdsGroupedByUserIds(array $userIds): array
    {
        $grouped = [];
        if ($userIds === []) {
            return $grouped;
        }
        $rows = StaffUserRelateNodeGroupEntity::queryActive()->whereIn('user_id', $userIds)->select()->toArray();
        foreach ($rows as $row) {
            $grouped[(int) $row['user_id']][] = (int) $row['node_group_id'];
        }

        return $grouped;
    }

    /**
     * @param array<int, array<string, mixed>> $roles
     */
    private function hasSuperRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!empty($role['isSuperRole'])) {
                return true;
            }
        }

        return false;
    }

    private function assertNotSelf(int $userId, string $message): void
    {
        $currentId = (int) (FrameworkContext::getUserId() ?? 0);
        if ($currentId > 0 && $currentId === $userId) {
            throw StaffException::throw($message, -1);
        }
    }
}
