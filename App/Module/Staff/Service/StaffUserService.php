<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use App\Module\Cron\Repository\CronAgentNodeGroupRepository;
use App\Module\Staff\Dto\StaffUser\CreateUserDto;
use App\Module\Staff\Dto\StaffUser\GeneratedResetPasswordDto;
use App\Module\Staff\Dto\StaffUser\GrantUserNodeGroupsDto;
use App\Module\Staff\Dto\StaffUser\GrantUserRolesDto;
use App\Module\Staff\Dto\StaffUser\ListUsersQueryDto;
use App\Module\Staff\Dto\StaffUser\ResetPasswordAckDto;
use App\Module\Staff\Dto\StaffUser\ResetUserPasswordDto;
use App\Module\Staff\Dto\StaffRole\StaffRoleBriefDto;
use App\Module\Staff\Dto\StaffUser\StaffNodeGroupBriefDto;
use App\Module\Staff\Dto\StaffUser\StaffUserBriefDto;
use App\Module\Staff\Dto\StaffUser\StaffUserRowDto;
use App\Module\Staff\Dto\StaffUser\SwitchUserStatusDto;
use App\Module\Staff\Dto\StaffUser\UpdateUserDto;
use App\Module\Staff\Dto\StaffUser\UserIdDto;
use App\Module\Staff\Entity\StaffUserEntity;
use App\Module\Staff\Exception\StaffException;
use App\Module\Staff\Repository\StaffUserRelateNodeGroupRepository;
use App\Module\Staff\Repository\StaffUserRepository;
use App\Module\Staff\Repository\StaffUserRoleRepository;
use App\Module\Staff\Response\StaffManager\ListUsersPageResult;
use App\Module\Staff\StaffRoleCode;
use Swoolefy\Support\FrameworkContext;

/**
 * 用户管理：增删改、角色 / 节点组授权、超级管理员重置密码。
 * 重置密码分两步：{@see generateResetPassword()} 只签发 32 位临时密码；
 * {@see resetPasswordBySuperAdmin()} 才写入哈希并尝试发邮件。
 */
class StaffUserService
{
    private StaffRoleService $staffRoleService {
        get => $this->staffRoleService ??= new StaffRoleService();
        set => $this->staffRoleService = $value;
    }

    /** 签发 / 解析 32 位临时重置密码。 */
    private ResetPasswordTokenService $resetPasswordTokenService {
        get => $this->resetPasswordTokenService ??= new ResetPasswordTokenService();
        set => $this->resetPasswordTokenService = $value;
    }

    /** 重置成功后按 .env 发邮件；未配置则跳过。 */
    private StaffMailService $staffMailService {
        get => $this->staffMailService ??= new StaffMailService();
        set => $this->staffMailService = $value;
    }

    private StaffUserRepository $userRepository {
        get => $this->userRepository ??= new StaffUserRepository();
    }

    private StaffUserRoleRepository $userRoleRepository {
        get => $this->userRoleRepository ??= new StaffUserRoleRepository();
    }

    private StaffUserRelateNodeGroupRepository $userNodeGroupRepository {
        get => $this->userNodeGroupRepository ??= new StaffUserRelateNodeGroupRepository();
    }

    private CronAgentNodeGroupRepository $nodeGroupRepository {
        get => $this->nodeGroupRepository ??= new CronAgentNodeGroupRepository();
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

        $pageResult = new ListUsersPageResult();
        $pageResult->setPage($query->getPage());
        $pageResult->setPageSize($query->getPageSize());
        $pageResult->setTotal($this->userRepository->countByListQuery($query));

        $rows = $this->userRepository->listRowsByListQuery($query);
        $userIds = array_map(static fn (\App\Module\Staff\Entity\StaffUserEntity $row): int => (int) $row->id, $rows);
        $rolesMap = $this->staffRoleService->rolesGroupedByUserIds($userIds);
        $groupsMap = $this->nodeGroupIdsGroupedByUserIds($userIds);
        $allGroupIds = [];
        foreach ($groupsMap as $ids) {
            foreach ($ids as $groupId) {
                $allGroupIds[$groupId] = $groupId;
            }
        }
        $groupRows = $this->nodeGroupsByIds(array_values($allGroupIds));

        foreach ($rows as $user) {
            $row = $user->getAttributes();
            $userId = (int) $row['id'];
            $roles = $rolesMap[$userId] ?? [];
            $groupIds = $groupsMap[$userId] ?? [];
            $row['roles'] = array_map(
                static fn (StaffRoleBriefDto $role): array => $role->toDeepArray(),
                $roles,
            );
            $row['role_ids'] = array_map(
                static fn (StaffRoleBriefDto $role): int => $role->getId(),
                $roles,
            );
            $row['node_group_ids'] = $groupIds;
            $row['node_groups'] = array_map(
                static fn (StaffNodeGroupBriefDto $group): array => $group->toDeepArray(),
                $this->nodeGroupsOfIdsAsDto($groupIds, $groupRows),
            );
            $row['is_super'] = $this->hasSuperRole($roles);
            $pageResult->addListItem(StaffUserRowDto::fromEntityRow($row));
        }

        return $pageResult;
    }

    public function createUser(CreateUserDto $dto): StaffUserRowDto
    {
        $this->assertAccountAndName($dto->getAccount(), $dto->getUserName());
        $this->assertPassword($dto->getPassword());
        $account = $dto->getAccount();
        $email = $this->resolveStoredEmail($account, $dto->getEmail());
        $this->assertAccountAvailable($account, $email, null);
        $user = $this->userRepository->insert([
            'account' => $account,
            'email' => $email,
            'user_name' => $dto->getUserName(),
            'password' => self::hashPassword($dto->getPassword()),
            'status' => 1,
            'enabled_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getUser(UserIdDto::of((int) $user->id));
    }

    public function updateUser(UpdateUserDto $dto): StaffUserRowDto
    {
        $user = $this->requireUser($dto->getId());
        $currentAccount = (string) ($user->account ?? '');
        $account = $dto->getAccount();
        if (self::isBuiltInAdminAccount($currentAccount)) {
            if ($account !== '' && !self::isBuiltInAdminAccount($account)) {
                throw StaffException::throw('系统内置账号 admin 不可修改', -1);
            }
            $account = $currentAccount;
        }
        $this->assertAccountAndName($account, $dto->getUserName());
        $email = $this->resolveStoredEmail($account, $dto->getEmail());
        $this->assertAccountAvailable($account, $email, $dto->getId());

        $data = [
            'account' => $account,
            'email' => $email,
            'user_name' => $dto->getUserName(),
        ];
        $this->userRepository->save($user->setData($data));

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
        return $this->userRepository->save($user->setData([
            'user_name' => $userName,
        ]));
    }

    public function getUser(UserIdDto $dto): StaffUserRowDto
    {
        return $this->buildUserRowDto($this->requireUser($dto->getId()));
    }

    public function grantRoles(GrantUserRolesDto $dto): StaffUserRowDto
    {
        $user = $this->requireUser($dto->getId());
        $this->staffRoleService->assertRolesExist($dto->getRoleIds());
        $this->replaceUserRoles((int) $user->id, $dto->getRoleIds());

        return $this->getUser(UserIdDto::of((int) $user->id));
    }

    public function grantNodeGroups(GrantUserNodeGroupsDto $dto): StaffUserRowDto
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

        return $this->userNodeGroupRepository->existsForUserAndGroup($userId, $nodeGroupId);
    }

    /**
     * @return list<StaffUserBriefDto>
     */
    public function listUsersByNodeGroup(int $nodeGroupId): array
    {
        $this->assertSuperViewer();

        if ($nodeGroupId <= 0) {
            return [];
        }

        $userIds = $this->userNodeGroupRepository->listUserIdsByNodeGroupId($nodeGroupId);
        if ($userIds === []) {
            return [];
        }

        $users = $this->userRepository->listActiveBriefRowsByIds($userIds);

        $list = [];
        foreach ($users as $user) {
            $list[] = StaffUserBriefDto::fromUserEntity($user);
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
            if ($role->getCode() === $roleCode) {
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
        if (self::isBuiltInAdminAccount((string) ($user->account ?? '')) || $this->isSuperUser((int) $user->id)) {
            throw StaffException::throw('超级管理员账号不能删除', -1);
        }
        $this->assertNotSelf((int) $user->id, '不能删除当前登录账号');

        $userId = (int) $user->id;
        $this->userRepository->save($user->setData([
            'status' => 0,
            'disabled_at' => date('Y-m-d H:i:s'),
        ]));
        $this->userRoleRepository->deleteByUserId($userId);
        $this->userNodeGroupRepository->deleteByUserId($userId);
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
        $this->userRepository->save($user->setData($data));

        return SwitchUserStatusDto::of((int) $user->id, $status);
    }

    /**
     * 超级管理员为其他用户签发 3 天有效的 32 位临时重置密码。
     * 此时尚未写入数据库，前端回填「重置的密码」后需再点确认。
     */
    public function generateResetPassword(int $userId): GeneratedResetPasswordDto
    {
        $this->assertSuperViewer();
        if ($userId <= 0) {
            throw StaffException::throw('用户不存在', -1);
        }
        $this->assertNotSelf($userId, '请使用「修改密码」功能修改自己的密码');
        $user = $this->requireUser($userId);
        $issued = $this->resetPasswordTokenService->issue((int) $user->id);

        return GeneratedResetPasswordDto::of(
            (int) $user->id,
            $issued['password'],
            $issued['expiresAt'],
            $this->notifyEmailOf($user),
        );
    }

    /**
     * 确认重置：校验传入的就是刚签发的临时密码（未过期、userId 匹配），
     * 再写入哈希。有邮箱则发信；发信失败不影响密码已改，由 mailSent 告知前端。
     */
    public function resetPasswordBySuperAdmin(ResetUserPasswordDto $dto): ResetPasswordAckDto
    {
        $this->assertSuperViewer();

        $targetId = $dto->getId();
        if ($targetId <= 0) {
            throw StaffException::throw('用户不存在', -1);
        }
        $this->assertNotSelf($targetId, '请使用「修改密码」功能修改自己的密码');

        $password = trim($dto->getPassword());
        if ($password === '') {
            throw StaffException::throw('请先生成重置密码', -1);
        }
        $parsed = $this->resetPasswordTokenService->parse($password);
        if ($parsed === null) {
            throw StaffException::throw('请先生成重置密码', -1);
        }
        if (!empty($parsed['expired'])) {
            throw StaffException::throw('临时重置密码已过期，请重新生成', -1);
        }
        if ((int) $parsed['userId'] !== $targetId) {
            throw StaffException::throw('重置密码与用户不匹配，请重新生成', -1);
        }

        $user = $this->requireUser($targetId);
        $this->userRepository->save($user->setData([
            'password' => self::hashResetPassword($password),
        ]));

        $email = $this->notifyEmailOf($user);
        $mailSent = false;
        if ($email !== '') {
            $mailSent = $this->staffMailService->sendResetPassword($email, $password);
        }

        return ResetPasswordAckDto::of((int) $user->id, $mailSent, $email);
    }

    /**
     * 通知邮箱：优先用户 email 字段，否则账号本身是邮箱则用账号。
     * 都没有则返回空串，前端提示需人为通知。
     */
    public function notifyEmailOf(StaffUserEntity $user): string
    {
        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return $email;
        }
        $fromAccount = self::emailFromAccount((string) ($user->account ?? ''));

        return $fromAccount ?? '';
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * 临时重置密码入库哈希。先 sha256 再 bcrypt，避免密码形态变化影响校验。
     */
    public static function hashResetPassword(string $password): string
    {
        return self::hashPassword(hash('sha256', $password));
    }

    /** 与 {@see hashResetPassword()} 成对，登录 / 改密时校验临时密码。 */
    public static function verifyResetPassword(string $password, string $hash): bool
    {
        return password_verify(hash('sha256', $password), $hash);
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
                throw StaffException::throw('邮箱格式不对', -1);
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
        $user = $this->userRepository->findById($id);
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
        $this->userRoleRepository->replaceForUser($userId, $roleIds);
    }

    /**
     * @param array<int, int> $groupIds
     */
    public function replaceUserNodeGroups(int $userId, array $groupIds): void
    {
        $this->userNodeGroupRepository->replaceForUser($userId, $groupIds);
    }

    /**
     * 账号是合法邮箱时返回该邮箱，否则返回 null。须先经过 {@see assertAccount()}。
     */
    public static function emailFromAccount(string $account): ?string
    {
        $account = trim($account);
        if ($account !== '' && filter_var($account, FILTER_VALIDATE_EMAIL) !== false) {
            return strtolower($account);
        }

        return null;
    }

    /**
     * 账号为邮箱时同步写入 email；否则使用提交的邮箱（空则清空）。
     */
    private function resolveStoredEmail(string $account, string $submittedEmail): ?string
    {
        $fromAccount = self::emailFromAccount($account);
        if ($fromAccount !== null) {
            return $fromAccount;
        }
        $submittedEmail = strtolower(trim($submittedEmail));
        if ($submittedEmail === '') {
            return null;
        }
        if (filter_var($submittedEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw StaffException::throw('邮箱格式不对', -1);
        }

        return $submittedEmail;
    }

    private function assertAccountAvailable(string $account, ?string $email, ?int $exceptId): void
    {
        if ($this->userRepository->existsAccount($account, $exceptId)) {
            throw StaffException::throw('账号已存在', -1);
        }
        $this->assertEmailAvailable($email, $exceptId);
    }

    private function assertEmailAvailable(?string $email, ?int $exceptId): void
    {
        if ($email === null || $email === '') {
            return;
        }
        if ($this->userRepository->findIdUsingEmail($email, $exceptId) !== null) {
            throw StaffException::throw('邮箱已被使用', -1);
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
        if ($this->nodeGroupRepository->countExistingIds($groupIds) !== count(array_unique($groupIds))) {
            throw StaffException::throw('节点组不存在', -1);
        }
    }

    /**
     * @param array<int, int> $groupIds
     * @return array<int, array{id:int,groupName:string}>
     */
    private function nodeGroupsByIds(array $groupIds): array
    {
        return $this->nodeGroupRepository->briefMapByIds($groupIds);
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
        return $this->userNodeGroupRepository->nodeGroupIdsGroupedByUserIds($userIds);
    }

    /**
     * @param list<StaffRoleBriefDto> $roles
     */
    private function hasSuperRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($role->getIsSuperRole()) {
                return true;
            }
        }

        return false;
    }

    private function buildUserRowDto(StaffUserEntity $user): StaffUserRowDto
    {
        $attrs = $user->getAttributes();
        unset($attrs['password']);
        $userId = (int) $user->id;
        $roles = $this->staffRoleService->rolesGroupedByUserIds([$userId])[$userId] ?? [];
        $groupIds = $this->nodeGroupIdsGroupedByUserIds([$userId])[$userId] ?? [];
        $attrs['roles'] = array_map(
            static fn (StaffRoleBriefDto $role): array => $role->toDeepArray(),
            $roles,
        );
        $attrs['role_ids'] = array_map(
            static fn (StaffRoleBriefDto $role): int => $role->getId(),
            $roles,
        );
        $attrs['node_group_ids'] = $groupIds;
        $attrs['node_groups'] = array_map(
            static fn (StaffNodeGroupBriefDto $group): array => $group->toDeepArray(),
            $this->nodeGroupsOfIdsAsDto($groupIds),
        );
        $attrs['is_super'] = $this->hasSuperRole($roles);

        return StaffUserRowDto::fromEntityRow($attrs);
    }

    /**
     * @param array<int, int> $groupIds
     * @param array<int, array{id:int,groupName:string}>|null $preloaded
     * @return list<StaffNodeGroupBriefDto>
     */
    private function nodeGroupsOfIdsAsDto(array $groupIds, ?array $preloaded = null): array
    {
        $map = $preloaded ?? $this->nodeGroupsByIds($groupIds);
        $list = [];
        foreach ($groupIds as $groupId) {
            if (isset($map[$groupId])) {
                $list[] = StaffNodeGroupBriefDto::fromSlice($map[$groupId]);
            }
        }

        return $list;
    }

    public static function isBuiltInAdminAccount(string $account): bool
    {
        return strcasecmp(trim($account), 'admin') === 0;
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
