<?php

declare(strict_types=1);

namespace App\Module\Staff\Controller;

use App\Module\Staff\Dto\StaffManager\LoginDto;
use App\Module\Staff\Dto\StaffManager\RegisterDto;
use App\Module\Staff\Request\StaffManager\LoginRequest;
use App\Module\Staff\Request\StaffManager\RegisterRequest;
use App\Module\Staff\Response\StaffManager\AuthMeResponse;
use App\Module\Staff\Response\StaffManager\LoginResponse;
use App\Module\Staff\Service\StaffAuthService;
use Swoolefy\Annotation\ApiOperation;
use Swoolefy\Core\Controller\BController;

/**
 * 登录 / 注册 / 当前用户。
 */
class StaffAuthController extends BController
{
    private StaffAuthService $staffAuthService {
        get => $this->staffAuthService ??= new StaffAuthService();
    }

    /**
     * Route: POST /api/v1/auth/register
     *
     ```bash
     curl -X POST 'http://127.0.0.1:9501/api/v1/auth/register' \
       -H 'Content-Type: application/json' \
       -d '{"account":"admin@example.com","userName":"管理员","password":"12345678","passwordConfirm":"12345678"}'
     ```
     */
    #[ApiOperation('用户注册')]
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
}
