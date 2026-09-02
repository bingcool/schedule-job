<?php

declare(strict_types=1);

namespace App\Module\Staff\Controller;

use App\Module\Staff\Dto\StaffManager\GrantRolePagesDto;
use App\Module\Staff\Dto\StaffManager\CreateMenuDto;
use App\Module\Staff\Dto\StaffManager\CreateRoleDto;
use App\Module\Staff\Dto\StaffManager\ListRolesQueryDto;
use App\Module\Staff\Dto\StaffManager\MenuIdDto;
use App\Module\Staff\Dto\StaffManager\RoleIdDto;
use App\Module\Staff\Dto\StaffManager\SortMenusDto;
use App\Module\Staff\Dto\StaffManager\SwitchMenuStatusDto;
use App\Module\Staff\Dto\StaffManager\SwitchRoleStatusDto;
use App\Module\Staff\Dto\StaffManager\UpdateMenuDto;
use App\Module\Staff\Dto\StaffManager\UpdateRoleDto;
use App\Module\Staff\Request\StaffManager\ListRolesRequest;
use App\Module\Staff\Request\StaffManager\StaffMenuCreateRequest;
use App\Module\Staff\Request\StaffManager\StaffMenuIdRequest;
use App\Module\Staff\Request\StaffManager\StaffMenuSortRequest;
use App\Module\Staff\Request\StaffManager\StaffMenuStatusRequest;
use App\Module\Staff\Request\StaffManager\StaffMenuUpdateRequest;
use App\Module\Staff\Request\StaffManager\StaffRoleCreateRequest;
use App\Module\Staff\Request\StaffManager\StaffRoleIdRequest;
use App\Module\Staff\Request\StaffManager\StaffRolePagesRequest;
use App\Module\Staff\Request\StaffManager\StaffRoleStatusRequest;
use App\Module\Staff\Request\StaffManager\StaffRoleUpdateRequest;
use App\Module\Staff\Response\StaffManager\ListRolesResponse;
use App\Module\Staff\Response\StaffManager\RoleOptionsResponse;
use App\Module\Staff\Response\StaffManager\RoleStatsResponse;
use App\Module\Staff\Response\StaffManager\StaffDeleteAckResponse;
use App\Module\Staff\Response\StaffManager\StaffMenuRowResponse;
use App\Module\Staff\Response\StaffManager\StaffMenuSortAckResponse;
use App\Module\Staff\Response\StaffManager\StaffMenuStatusAckResponse;
use App\Module\Staff\Response\StaffManager\StaffMenuTreeResponse;
use App\Module\Staff\Response\StaffManager\StaffRoleRowResponse;
use App\Module\Staff\Response\StaffManager\StaffRoleStatusAckResponse;
use App\Module\Staff\Service\StaffRoleService;
use Swoolefy\Annotation\ApiOperation;
use Swoolefy\Core\Controller\BController;

/**
 * 权限组（角色）与菜单管理。
 */
class StaffRoleController extends BController
{
    private StaffRoleService $staffRoleService {
        get => $this->staffRoleService ??= new StaffRoleService();
    }

    /**
     * Route: GET /api/v1/roles
     */
    #[ApiOperation('分页查询角色')]
    public function listRoles(ListRolesRequest $request): ListRolesResponse
    {
        $query = (new ListRolesQueryDto())
            ->setPage($request->getPage())
            ->setPageSize($request->getPageSize())
            ->setName($request->getName())
            ->setStatus($request->getStatus());

        return new ListRolesResponse($this->staffRoleService->listRoles($query));
    }

    /**
     * Route: GET /api/v1/roles/stats
     */
    #[ApiOperation('角色统计')]
    public function roleStats(): RoleStatsResponse
    {
        return new RoleStatsResponse($this->staffRoleService->roleStats());
    }

    /**
     * Route: GET /api/v1/roles/options
     */
    #[ApiOperation('角色下拉选项')]
    public function listRoleOptions(): RoleOptionsResponse
    {
        return new RoleOptionsResponse($this->staffRoleService->listRoleOptions());
    }

    /**
     * Route: POST /api/v1/roles
     */
    #[ApiOperation('创建角色')]
    public function createRole(StaffRoleCreateRequest $request): StaffRoleRowResponse
    {
        $dto = (new CreateRoleDto())
            ->setName($request->getName())
            ->setCode($request->getCode())
            ->setDesc($request->getDesc())
            ->setStatus($request->getStatus());

        return new StaffRoleRowResponse($this->staffRoleService->createRole($dto));
    }

    /**
     * Route: PUT /api/v1/roles
     */
    #[ApiOperation('更新角色')]
    public function updateRole(StaffRoleUpdateRequest $request): StaffRoleRowResponse
    {
        $dto = (new UpdateRoleDto())
            ->setId($request->getId())
            ->setName($request->getName())
            ->setCode($request->getCode())
            ->setDesc($request->getDesc())
            ->setStatus($request->getStatus());

        return new StaffRoleRowResponse($this->staffRoleService->updateRole($dto));
    }

    /**
     * Route: GET /api/v1/roles/detail?id=
     */
    #[ApiOperation('角色详情')]
    public function getRole(StaffRoleIdRequest $request): StaffRoleRowResponse
    {
        return new StaffRoleRowResponse($this->staffRoleService->getRole(RoleIdDto::of($request->getId())));
    }

    /**
     * Route: DELETE /api/v1/roles
     */
    #[ApiOperation('删除角色')]
    public function deleteRole(StaffRoleIdRequest $request): StaffDeleteAckResponse
    {
        return new StaffDeleteAckResponse($this->staffRoleService->deleteRole(RoleIdDto::of($request->getId())));
    }

    /**
     * 启用或禁用角色（status：1=启用，0=禁用）。
     *
     * Route: PUT /api/v1/roles/status
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9501/api/v1/roles/status' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"id": 2, "status": 0}'
     ```
     */
    #[ApiOperation('启用或禁用角色')]
    public function switchStatus(StaffRoleStatusRequest $request): StaffRoleStatusAckResponse
    {
        $ack = $this->staffRoleService->switchStatus(
            SwitchRoleStatusDto::of($request->getId(), $request->getStatus())
        );

        return new StaffRoleStatusAckResponse($ack->getId(), $ack->getStatus());
    }

    /**
     * 独立配置角色菜单页面权限（staff_role_page）。
     *
     * Route: PUT /api/v1/roles/pages
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9501/api/v1/roles/pages' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"id": 2, "pageIds": [1, 2, 3, 4, 5]}'
     ```
     */
    #[ApiOperation('配置角色菜单页面权限')]
    public function grantRolePages(StaffRolePagesRequest $request): StaffRoleRowResponse
    {
        return new StaffRoleRowResponse($this->staffRoleService->grantRolePages(
            GrantRolePagesDto::of($request->getId(), $request->getPageIds())
        ));
    }

    /**
     * Route: GET /api/v1/menus
     */
    #[ApiOperation('菜单树')]
    public function listMenus(): StaffMenuTreeResponse
    {
        return new StaffMenuTreeResponse($this->staffRoleService->listMenus());
    }

    /**
     * Route: POST /api/v1/menus
     */
    #[ApiOperation('创建菜单')]
    public function createMenu(StaffMenuCreateRequest $request): StaffMenuRowResponse
    {
        $dto = (new CreateMenuDto())
            ->setName($request->getName())
            ->setCode($request->getCode())
            ->setUri($request->getUri())
            ->setIcon($request->getIcon())
            ->setParentId($request->getParentId())
            ->setSort($request->getSort());

        return new StaffMenuRowResponse($this->staffRoleService->createMenu($dto));
    }

    /**
     * Route: PUT /api/v1/menus
     */
    #[ApiOperation('更新菜单')]
    public function updateMenu(StaffMenuUpdateRequest $request): StaffMenuRowResponse
    {
        $dto = (new UpdateMenuDto())
            ->setId($request->getId())
            ->setName($request->getName())
            ->setCode($request->getCode())
            ->setUri($request->getUri())
            ->setIcon($request->getIcon())
            ->setParentId($request->getParentId())
            ->setSort($request->getSort());

        return new StaffMenuRowResponse($this->staffRoleService->updateMenu($dto));
    }

    /**
     * Route: PUT /api/v1/menus/status
     */
    #[ApiOperation('启用或禁用菜单')]
    public function switchMenuStatus(StaffMenuStatusRequest $request): StaffMenuStatusAckResponse
    {
        $ack = $this->staffRoleService->switchMenuStatus(
            SwitchMenuStatusDto::of($request->getId(), $request->getStatus())
        );

        return new StaffMenuStatusAckResponse($ack->getId(), $ack->getStatus());
    }

    /**
     * Route: PUT /api/v1/menus/sort
     */
    #[ApiOperation('同级菜单排序')]
    public function sortMenus(StaffMenuSortRequest $request): StaffMenuSortAckResponse
    {
        $ids = $this->staffRoleService->sortMenus(
            SortMenusDto::of($request->getParentId(), $request->getIds())
        );

        return new StaffMenuSortAckResponse($request->getParentId(), $ids);
    }

    /**
     * Route: GET /api/v1/menus/detail?id=
     */
    #[ApiOperation('菜单详情')]
    public function getMenu(StaffMenuIdRequest $request): StaffMenuRowResponse
    {
        return new StaffMenuRowResponse($this->staffRoleService->getMenu(MenuIdDto::of($request->getId())));
    }

    /**
     * Route: DELETE /api/v1/menus
     */
    #[ApiOperation('删除菜单')]
    public function deleteMenu(StaffMenuIdRequest $request): StaffDeleteAckResponse
    {
        return new StaffDeleteAckResponse($this->staffRoleService->deleteMenu(MenuIdDto::of($request->getId())));
    }
}
