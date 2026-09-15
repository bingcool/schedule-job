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

/**
 * 登录、改密、个人资料。
 * 登录会先用 {@see ResetPasswordTokenService::parse()} 判断是否临时重置密码：
 * 能解析则校验 3 天有效期；解析不出则按普通密码验证。
 */
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

    /** 登录时解析密码，区分临时重置密码与普通密码。 */
    private ResetPasswordTokenService $resetPasswordTokenService {
        get => $this->resetPasswordTokenService ??= new ResetPasswordTokenService();
        set => $this->resetPasswordTokenService = $value;
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
        throw StaffException::throw('系统已关闭公开注册，请联系管理员创建账号', -1);
    }

    /**
     * 登录。
     * 1）密码能被 ResetPasswordTokenService 解析 → 临时重置密码：先判过期，再核对 userId 与库中哈希。
     * 2）解析失败 → 普通密码 password_verify。
     * 临时登录会在 session 里带 loginMode=temp 和到期时间，供前端顶部 ❗ 提示。
     */
    public function login(LoginDto $dto): AuthSessionDto
    {
        $account = $dto->getAccount();
        if ($account === '' || $dto->getPassword() === '') {
            throw StaffException::throw('账号和密码不能为空', -1);
        }
        if (str_contains($account, '@') && filter_var($account, FILTER_VALIDATE_EMAIL) === false) {
            throw StaffException::throw('邮箱格式不对', -1);
        }

        $password = $dto->getPassword();
        $temp = $this->resetPasswordTokenService->parse($password);
        if ($temp !== null) {
            if (!empty($temp['expired'])) {
                throw StaffException::throw('临时重置密码已过期', -1);
            }
            $user = (new StaffUserEntity())->loadByLoginIdentity($account);
            if (
                !$user
                || $user->isDeleted()
                || (int) $user->id !== (int) $temp['userId']
                || !StaffUserService::verifyResetPassword($password, (string) $user->password)
            ) {
                throw StaffException::throw('账号或密码错误', -1);
            }
            if ($user->isDisabled()) {
                throw StaffException::throw('账号已禁用', -1);
            }

            return $this->issueSession($user, 'temp', (string) $temp['expiresAt']);
        }

        $user = (new StaffUserEntity())->loadByLoginIdentity($account);
        if (!$user || $user->isDeleted() || !password_verify($password, (string) $user->password)) {
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

    /**
     * 当前用户修改自己的密码。旧密码可能是普通密码，也可能是尚未过期的临时重置密码。
     */
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
        if (!$this->verifyStoredPassword($dto->getOldPassword(), (string) $user->password)) {
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

    /**
     * 校验库中密码哈希：先按普通 bcrypt，再按临时重置密码（sha256 后再 bcrypt）。
     */
    private function verifyStoredPassword(string $plain, string $hash): bool
    {
        if (password_verify($plain, $hash)) {
            return true;
        }

        return $this->resetPasswordTokenService->parse($plain) !== null
            && StaffUserService::verifyResetPassword($plain, $hash);
    }

    /**
     * 签发登录 session。
     * $loginMode=temp 时带上临时密码到期时间，前端按 user_id 写入 localStorage。
     */
    private function issueSession(
        StaffUserEntity $user,
        string $loginMode = 'normal',
        string $tempPasswordExpiresAt = '',
    ): AuthSessionDto {
        $profile = $this->profileOf($user);
        $this->assertHasMenuAccess($profile);
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

        return AuthSessionDto::of($token, $ttl, $profile, $loginMode, $tempPasswordExpiresAt);
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
            'email' => (string) ($user->email ?? ''),
            'userName' => (string) $user->user_name,
            'isSuper' => $isSuper,
            'isEditorTaskGroup' => $this->staffUserService->isEditorTaskGroupUser((int) $user->id),
            'roles' => $roles,
            'nodeGroupIds' => $this->staffUserService->nodeGroupIdsOfUser((int) $user->id),
            'menus' => $this->staffRoleService->menusForUser((int) $user->id, $isSuper),
        ];
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function assertHasMenuAccess(array $profile): void
    {
        if (!empty($profile['isSuper'])) {
            return;
        }
        foreach ($profile['menus'] ?? [] as $group) {
            if (!is_array($group)) {
                continue;
            }
            foreach ($group['children'] ?? [] as $item) {
                if (is_array($item) && trim((string) ($item['uri'] ?? '')) !== '') {
                    return;
                }
            }
        }

        throw StaffException::throw('您无菜单权限，暂无法进入系统，请联系管理员分配角色', -1);
    }
}
