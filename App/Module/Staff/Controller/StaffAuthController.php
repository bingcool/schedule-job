<?php

declare(strict_types=1);

namespace App\Module\Staff\Controller;

use App\Module\Staff\Dto\StaffManager\ChangePasswordDto;
use App\Module\Staff\Dto\StaffManager\LoginDto;
use App\Module\Staff\Dto\StaffManager\RegisterDto;
use App\Module\Staff\Dto\StaffManager\UpdateProfileDto;
use App\Module\Staff\Request\StaffManager\ChangePasswordRequest;
use App\Module\Staff\Request\StaffManager\LoginRequest;
use App\Module\Staff\Request\StaffManager\RegisterRequest;
use App\Module\Staff\Request\StaffManager\UpdateProfileRequest;
use App\Module\Staff\Response\StaffManager\AuthMeResponse;
use App\Module\Staff\Response\StaffManager\ChangePasswordAckResponse;
use App\Module\Staff\Response\StaffManager\LoginResponse;
use App\Module\Staff\Service\StaffAuthService;
use Swoolefy\Annotation\ApiOperation;
use Swoolefy\Core\Controller\BController;

/**
 * 登录 / 当前用户（公开注册已关闭，用户由管理员在后台创建）。
 */
class StaffAuthController extends BController
{
    private StaffAuthService $staffAuthService {
        get => $this->staffAuthService ??= new StaffAuthService();
    }

    /**
     * 公开注册已关闭，保留方法供兼容；实际应通过管理员在用户管理中创建账号。
     *
     * @deprecated 路由已移除
     */
    #[ApiOperation('用户注册（已关闭）')]
    public function register(RegisterRequest $request): LoginResponse
    {
        return new LoginResponse($this->staffAuthService->register(
            RegisterDto::of(
                $request->getAccount(),
                $request->getUserName(),
                $request->getPassword(),
                $request->getPasswordConfirm(),
            )
        ));
    }

    /**
     * Route: POST /api/v1/auth/login
     *
     ```bash
     curl -X POST 'http://127.0.0.1:9501/api/v1/auth/login' \
       -H 'Content-Type: application/json' \
       -d '{"account":"admin@example.com","password":"12345678"}'
     ```
     */
    #[ApiOperation('用户登录')]
    public function login(LoginRequest $request): LoginResponse
    {
        return new LoginResponse($this->staffAuthService->login(
            LoginDto::of($request->getAccount(), $request->getPassword())
        ));
    }

    /**
     * Route: GET /api/v1/auth/me
     *
     ```bash
     curl -X GET 'http://127.0.0.1:9501/api/v1/auth/me' \
       -H 'Authorization: Bearer <jwt>'
     ```
     */
    #[ApiOperation('当前登录用户')]
    public function me(): AuthMeResponse
    {
        return new AuthMeResponse($this->staffAuthService->me());
    }

    /**
     * 当前登录用户修改自己的密码。
     *
     * Route: PUT /api/v1/auth/password
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9501/api/v1/auth/password' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"oldPassword":"12345678","newPassword":"87654321","newPasswordConfirm":"87654321"}'
     ```
     */
    #[ApiOperation('修改当前用户密码')]
    public function changePassword(ChangePasswordRequest $request): ChangePasswordAckResponse
    {
        $id = $this->staffAuthService->changePassword(ChangePasswordDto::of(
            $request->getOldPassword(),
            $request->getNewPassword(),
            $request->getNewPasswordConfirm(),
        ));

        return new ChangePasswordAckResponse($id);
    }

    /**
     * 当前登录用户修改自己的用户名称（账号不可改）。
     *
     * Route: PUT /api/v1/auth/profile
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9501/api/v1/auth/profile' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"userName":"运维"}'
     ```
     */
    #[ApiOperation('修改当前用户资料')]
    public function updateProfile(UpdateProfileRequest $request): AuthMeResponse
    {
        return new AuthMeResponse($this->staffAuthService->updateProfile(
            UpdateProfileDto::of($request->getUserName())
        ));
    }
}
