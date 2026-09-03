<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use App\Module\Staff\Dto\StaffManager\AuthSessionDto;
use App\Module\Staff\Dto\StaffManager\ChangePasswordDto;
use App\Module\Staff\Dto\StaffManager\LoginDto;
use App\Module\Staff\Dto\StaffManager\RegisterDto;
use App\Module\Staff\Dto\StaffManager\UpdateProfileDto;
use App\Module\Staff\Entity\StaffUserEntity;
use App\Module\Staff\Exception\StaffException;
use Swoolefy\Core\Application;
use Swoolefy\Support\Auth\AuthUser;
use Swoolefy\Support\Auth\JwtAuthGuard;
use Swoolefy\Support\FrameworkContext;

class StaffAuthService
{
    private StaffRoleService $staffRoleService {
        get => $this->staffRoleService ??= new StaffRoleService();
        set => $this->staffRoleService = $value;
    }

    private StaffUserService $staffUserService {
        get => $this->staffUserService ??= new StaffUserService($this->staffRoleService);
        set => $this->staffUserService = $value;
    }

    public function __construct(
        ?StaffRoleService $staffRoleService = null,
        ?StaffUserService $staffUserService = null,
    ) {
        if ($staffRoleService !== null) {
            $this->staffRoleService = $staffRoleService;
        }
        if ($staffUserService !== null) {
            $this->staffUserService = $staffUserService;
        }
    }

    public function register(RegisterDto $dto): AuthSessionDto
    {
        $account = trim($dto->getAccount());
        $userName = trim($dto->getUserName());
        if ($account === '' || $userName === '') {
            throw StaffException::throw('账号和用户名称不能为空', -1);
        }
        if ($dto->getPassword() !== $dto->getPasswordConfirm()) {
            throw StaffException::throw('两次输入的密码不一致', -1);
        }
        StaffUserService::assertAccount($account);
        StaffUserService::assertPassword($dto->getPassword());
        if ((new StaffUserEntity())->loadByAccount($account)) {
            throw StaffException::throw('账号已存在', -1);
        }

        $isFirstUser = (int) StaffUserEntity::query()->count() === 0;

        $user = new StaffUserEntity();
        $user->setData([
            'account' => $account,
            'user_name' => $userName,
            'password' => StaffUserService::hashPassword($dto->getPassword()),
            'status' => 1,
            'enabled_at' => date('Y-m-d H:i:s'),
        ]);
        $user->save();

        if ($isFirstUser) {
            $super = $this->staffRoleService->ensureBootstrap();
            $this->staffUserService->replaceUserRoles((int) $user->id, [(int) $super->id]);
        }

        return $this->issueSession($user);
    }

    public function login(LoginDto $dto): AuthSessionDto
    {
        $account = $dto->getAccount();
        if ($account === '' || $dto->getPassword() === '') {
            throw StaffException::throw('账号和密码不能为空', -1);
        }

        $user = (new StaffUserEntity())->loadByAccount($account);
        if (!$user || $user->isDeleted() || !password_verify($dto->getPassword(), (string) $user->password)) {
            throw StaffException::throw('账号或密码错误', -1);
        }
        if ($user->isDisabled()) {
            throw StaffException::throw('账号已禁用', -1);
        }

        return $this->issueSession($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function me(): array
    {
        $authUser = FrameworkContext::userOrFail();
        $user = $this->staffUserService->requireUser((int) $authUser->userId);
        if ($user->isDisabled()) {
            throw StaffException::throw('账号已禁用', -1);
        }

        return $this->profileOf($user);
    }

    public function changePassword(ChangePasswordDto $dto): int
    {
        $authUser = FrameworkContext::userOrFail();
        $user = $this->staffUserService->requireUser((int) $authUser->userId);
        if ($user->isDisabled()) {
            throw StaffException::throw('账号已禁用', -1);
        }
        if ($dto->getOldPassword() === '' || $dto->getNewPassword() === '') {
            throw StaffException::throw('旧密码和新密码不能为空', -1);
        }
        if ($dto->getNewPassword() !== $dto->getNewPasswordConfirm()) {
            throw StaffException::throw('两次输入的新密码不一致', -1);
        }
        if (!password_verify($dto->getOldPassword(), (string) $user->password)) {
            throw StaffException::throw('旧密码不正确', -1);
        }
        if (password_verify($dto->getNewPassword(), (string) $user->password)) {
            throw StaffException::throw('新密码不能与旧密码相同', -1);
        }
        StaffUserService::assertPassword($dto->getNewPassword());

        $user->setData([
            'password' => StaffUserService::hashPassword($dto->getNewPassword()),
        ]);
        $user->save();

        return (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function updateProfile(UpdateProfileDto $dto): array
    {
        $authUser = FrameworkContext::userOrFail();
        $current = $this->staffUserService->requireUser((int) $authUser->userId);
        if ($current->isDisabled()) {
            throw StaffException::throw('账号已禁用', -1);
        }
        $user = $this->staffUserService->updateSelfProfile(
            (int) $current->id,
            $dto->getUserName(),
        );

        return $this->profileOf($user);
    }

    private function issueSession(StaffUserEntity $user): AuthSessionDto
    {
        $profile = $this->profileOf($user);
        $roleCodes = array_values(array_filter(array_map(
            static fn (array $role): string => (string) ($role['code'] ?? ''),
            $profile['roles'] ?? []
        )));
        if (!empty($profile['isSuper']) && !in_array('admin', $roleCodes, true)) {
            $roleCodes[] = 'admin';
        }

        /** @var JwtAuthGuard $guard */
        $guard = Application::getApp()->get('auth.guard');
        $token = $guard->generateToken(new AuthUser(
            userId: (string) $user->id,
            roles: $roleCodes,
        ));

        $authConfig = include APP_PATH . '/Config/auth.php';
        $ttl = (int) ($authConfig['jwt']['ttl_seconds'] ?? 3600);

        return AuthSessionDto::of($token, $ttl, $profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function profileOf(StaffUserEntity $user): array
    {
        $roles = $this->staffRoleService->rolesGroupedByUserIds([(int) $user->id])[(int) $user->id] ?? [];
        $isSuper = false;
        foreach ($roles as $role) {
            if (!empty($role['isSuperRole'])) {
                $isSuper = true;
                break;
            }
        }

        return [
            'id' => (int) $user->id,
            'account' => (string) $user->account,
            'userName' => (string) $user->user_name,
            'isSuper' => $isSuper,
            'isEditorTaskGroup' => $this->staffUserService->isEditorTaskGroupUser((int) $user->id),
            'roles' => $roles,
            'nodeGroupIds' => $this->staffUserService->nodeGroupIdsOfUser((int) $user->id),
            'menus' => $this->staffRoleService->menusForUser((int) $user->id, $isSuper),
        ];
    }
}
