<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use App\Module\Cron\Entity\CronAgentNodeGroupEntity;
use App\Module\Staff\Dto\StaffManager\CreateUserDto;
use App\Module\Staff\Dto\StaffManager\GrantUserNodeGroupsDto;
use App\Module\Staff\Dto\StaffManager\GrantUserRolesDto;
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
use App\Module\Staff\StaffRoleCode;
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

        $qb = StaffUserEntity::queryActive();
        if ($account !== '') {
            $qb->where('account', 'like', '%' . $account . '%');
        }
        if ($userName !== '') {
            $qb->where('user_name', 'like', '%' . $userName . '%');
        }
        if ($status === 1 || $status === 0) {
            $qb->where('status', $status);
        }

        $pageResult = new ListUsersPageResult();
        $pageResult->setPage($query->getPage());
        $pageResult->setPageSize($query->getPageSize());
        $pageResult->setTotal((int) $qb->clone()->count());

        $rows = $qb->order('id', 'desc')->limit($query->getOffset(), $query->getPageSize())->select()->toArray();
        $userIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $rolesMap = $this->staffRoleService->rolesGroupedByUserIds($userIds);
        $groupsMap = $this->nodeGroupIdsGroupedByUserIds($userIds);
        $allGroupIds = [];
        foreach ($groupsMap as $ids) {
            foreach ($ids as $groupId) {
                $allGroupIds[$groupId] = $groupId;
            }
        }
        $groupRows = $this->nodeGroupsByIds(array_values($allGroupIds));

        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $roles = $rolesMap[$userId] ?? [];
            $groupIds = $groupsMap[$userId] ?? [];
            $row['roles'] = $roles;
            $row['role_ids'] = array_map(static fn (array $role): int => (int) $role['id'], $roles);
            $row['node_group_ids'] = $groupIds;
            $row['node_groups'] = $this->nodeGroupsOfIds($groupIds, $groupRows);
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
        $user = new StaffUserEntity();
        $user->setData([
            'account' => $dto->getAccount(),
            'user_name' => $dto->getUserName(),
            'password' => self::hashPassword($dto->getPassword()),
            'status' => 1,
            'enabled_at' => date('Y-m-d H:i:s'),
        ]);
        $user->save();

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

        $data = [
            'account' => $dto->getAccount(),
            'user_name' => $dto->getUserName(),
        ];
        $user->setData($data);
        $user->save();

        return $this->getUser(UserIdDto::of((int) $user->id));
    }

    /**
     * @return StaffUserEntity
     */
    public function updateSelfProfile(int $userId, string $userName): StaffUserEntity
    {
        $user = $this->requireUser($userId);
        $userName = trim($userName);
        if ($userName === '') {
            throw StaffException::throw('用户名称不能为空', -1);
        }
        $user->setData([
            'user_name' => $userName,
        ]);
        $user->save();

        return $user;
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
        $attrs['node_groups'] = $this->nodeGroupsOfIds($attrs['node_group_ids']);
        $attrs['is_super'] = $this->hasSuperRole($roles);

        return $attrs;
    }

    /**
     * @return array<string, mixed>
     */
    public function grantRoles(GrantUserRolesDto $dto): array
    {
        $user = $this->requireUser($dto->getId());
        $this->staffRoleService->assertRolesExist($dto->getRoleIds());
        $this->replaceUserRoles((int) $user->id, $dto->getRoleIds());

        return $this->getUser(UserIdDto::of((int) $user->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function grantNodeGroups(GrantUserNodeGroupsDto $dto): array
    {
        $user = $this->requireUser($dto->getId());
        if ($this->isSuperUser((int) $user->id)) {
            throw StaffException::throw('超级管理员固定拥有所有节点，无需单独授权', -1);
        }
        $this->assertNodeGroupsExist($dto->getNodeGroupIds());
        $viewerGroups = $this->viewerAuthorizedNodeGroupIds();
        if ($viewerGroups !== null) {
            foreach ($dto->getNodeGroupIds() as $groupId) {
                if (!in_array($groupId, $viewerGroups, true)) {
                    throw StaffException::throw('不能授权自己无权管理的节点组', -1);
                }
            }
        }
        $this->replaceUserNodeGroups((int) $user->id, $dto->getNodeGroupIds());

        return $this->getUser(UserIdDto::of((int) $user->id));
    }

    /**
     * @return array<int, int>
     */
    public function nodeGroupIdsOfUser(int $userId): array
    {
        return $this->nodeGroupIdsGroupedByUserIds([$userId])[$userId] ?? [];
    }

    public function userHasNodeGroup(int $userId, int $nodeGroupId): bool
    {
        if ($userId <= 0 || $nodeGroupId <= 0) {
            return false;
        }

        return StaffUserRelateNodeGroupEntity::queryActive()
            ->where('user_id', $userId)
            ->where('node_group_id', $nodeGroupId)
            ->count() > 0;
    }

    /**
     * @return list<array{id:int,account:string,userName:string}>
     */
    public function listUsersByNodeGroup(int $nodeGroupId): array
    {
        $this->assertSuperViewer();

        if ($nodeGroupId <= 0) {
            return [];
        }

        $rows = StaffUserRelateNodeGroupEntity::queryActive()
            ->where('node_group_id', $nodeGroupId)
            ->field(['user_id'])
            ->select()
            ->toArray();
        $userIds = [];
        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId > 0) {
                $userIds[$userId] = $userId;
            }
        }
        if ($userIds === []) {
            return [];
        }

        $users = StaffUserEntity::queryActive()
            ->whereIn('id', array_values($userIds))
            ->where('status', 1)
            ->field(['id', 'account', 'user_name'])
            ->order('id', 'desc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($users as $user) {
            $list[] = [
                'id' => (int) ($user['id'] ?? 0),
                'account' => (string) ($user['account'] ?? ''),
                'userName' => (string) ($user['user_name'] ?? ''),
            ];
        }

        return $list;
    }

    public function isSuperUser(int $userId): bool
    {
        $roles = $this->staffRoleService->rolesGroupedByUserIds([$userId])[$userId] ?? [];

        return $this->hasSuperRole($roles);
    }

    public function hasRoleCode(int $userId, string $roleCode): bool
    {
        if ($userId <= 0 || $roleCode === '') {
            return false;
        }
        $roles = $this->staffRoleService->rolesGroupedByUserIds([$userId])[$userId] ?? [];
        foreach ($roles as $role) {
            if (($role['code'] ?? '') === $roleCode) {
                return true;
            }
        }

        return false;
    }

    public function isEditorTaskGroupUser(int $userId): bool
    {
        return $this->hasRoleCode($userId, StaffRoleCode::EDITOR_TASK_GROUP);
    }

    public function canManageCronTask(int $userId, int $createdBy): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if ($this->isSuperUser($userId)) {
            return true;
        }
        if ($this->isEditorTaskGroupUser($userId)) {
            return true;
        }

        return $createdBy > 0 && $createdBy === $userId;
    }

    /**
     * 当前登录者可查看的节点组。null=超级管理员不限制；[]=未授权任何节点组。
     *
     * @return array<int, int>|null
     */
    public function viewerAuthorizedNodeGroupIds(): ?array
    {
        $userId = (int) (FrameworkContext::getUserId() ?? 0);
        if ($userId <= 0) {
            return [];
        }
        if ($this->isSuperUser($userId)) {
            return null;
        }

        return $this->nodeGroupIdsOfUser($userId);
    }

    public function deleteUser(UserIdDto $dto): int
    {
        $user = $this->requireUser($dto->getId());
        $this->assertNotSelf((int) $user->id, '不能删除当前登录账号');

        $userId = (int) $user->id;
        $user->setData([
            'status' => 0,
            'disabled_at' => date('Y-m-d H:i:s'),
        ]);
        $user->save();
        StaffUserRoleEntity::query()->where('user_id', $userId)->delete();
        StaffUserRelateNodeGroupEntity::query()->where('user_id', $userId)->delete();
        $user->delete();

        return $userId;
    }

    public function switchStatus(SwitchUserStatusDto $dto): SwitchUserStatusDto
    {
        $user = $this->requireUser($dto->getId());
        $status = $dto->getStatus();
        if ($status === 0) {
            $this->assertNotSelf((int) $user->id, '不能禁用当前登录账号');
        }

        $now = date('Y-m-d H:i:s');
        $data = ['status' => $status];
        if ($status === 1) {
            $data['enabled_at'] = $now;
        } else {
            $data['disabled_at'] = $now;
        }
        $user->setData($data);
        $user->save();

        return SwitchUserStatusDto::of((int) $user->id, $status);
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

    /**
     * 账号格式：含 @ 时按邮箱校验；否则仅允许大小写字母与数字。
     */
    public static function assertAccount(string $account): void
    {
        $account = trim($account);
        if ($account === '') {
            throw StaffException::throw('账号不能为空', -1);
        }
        if (strlen($account) > 128) {
            throw StaffException::throw('账号长度不能超过128个字符', -1);
        }
        if (str_contains($account, '@')) {
            if (filter_var($account, FILTER_VALIDATE_EMAIL) === false) {
                throw StaffException::throw('请输入有效的邮箱地址', -1);
            }

            return;
        }
        if (!preg_match('/^[A-Za-z0-9]+$/', $account)) {
            throw StaffException::throw('账号仅支持大小写字母和数字，或使用有效邮箱', -1);
        }
    }

    public function requireUser(int $id): StaffUserEntity
    {
        if ($id <= 0) {
            throw StaffException::throw('id不能为空', -1);
        }
        $user = (new StaffUserEntity())->loadById($id);
        if (!$user || $user->isDeleted()) {
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
        if ($userName === '') {
            throw StaffException::throw('用户名称不能为空', -1);
        }
        self::assertAccount($account);
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
     * @param array<int, int> $groupIds
     * @return array<int, array{id:int,groupName:string}>
     */
    private function nodeGroupsByIds(array $groupIds): array
    {
        $map = [];
        if ($groupIds === []) {
            return $map;
        }
        $rows = CronAgentNodeGroupEntity::query()->whereIn('id', $groupIds)->select()->toArray();
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $map[$id] = [
                'id' => $id,
                'groupName' => (string) ($row['group_name'] ?? ''),
            ];
        }

        return $map;
    }

    /**
     * @param array<int, int> $groupIds
     * @param array<int, array{id:int,groupName:string}>|null $preloaded
     * @return array<int, array{id:int,groupName:string}>
     */
    private function nodeGroupsOfIds(array $groupIds, ?array $preloaded = null): array
    {
        $map = $preloaded ?? $this->nodeGroupsByIds($groupIds);
        $list = [];
        foreach ($groupIds as $groupId) {
            if (isset($map[$groupId])) {
                $list[] = $map[$groupId];
            }
        }

        return $list;
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

    private function assertSuperViewer(): void
    {
        $userId = (int) (FrameworkContext::getUserId() ?? 0);
        if ($userId <= 0 || !$this->isSuperUser($userId)) {
            throw StaffException::throw('无权限操作', -1);
        }
    }
}
