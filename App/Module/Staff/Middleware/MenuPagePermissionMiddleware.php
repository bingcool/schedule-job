<?php

declare(strict_types=1);

namespace App\Module\Staff\Middleware;

use App\Module\Staff\Service\StaffMenuPermissionService;
use Swoolefy\Core\RouteMiddlewareInterface;
use Swoolefy\Http\RequestInput;
use Swoolefy\Http\ResponseOutput;

/**
 * 管理端 API 菜单页面权限中间件（staff_role_page）。
 * 需排在 {@see \Swoolefy\Http\Middleware\AuthenticateMiddleware} 之后。
 */
class MenuPagePermissionMiddleware implements RouteMiddlewareInterface
{
    private StaffMenuPermissionService $menuPermissionService {
        get => $this->menuPermissionService ??= new StaffMenuPermissionService();
    }

    public function __construct()
    {
    }

    public function handle(RequestInput $requestInput, ResponseOutput $responseOutput): bool
    {
        $this->menuPermissionService->assertApiAllowed($requestInput->getRequestUri());

        return true;
    }
}
