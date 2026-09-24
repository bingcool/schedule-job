<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface;

use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\ListRolesRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffMenuCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffMenuIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffMenuSortRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffMenuStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffMenuUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffRoleCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffRoleIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffRolePagesRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffRoleStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffRoleUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ListRolesResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\RoleOptionsResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\RoleStatsResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffDeleteAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffMenuRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffMenuSortAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffMenuStatusAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffMenuTreeResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffRoleRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffRoleStatusAckResponse;
use InterfaceApi\Support\ApiController;
use InterfaceApi\Support\ApiOperation;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

/**
 * 权限组（角色）与菜单管理。
 *
 * 入参 / 出参字段定义见各 Request、Response 及其 DTO 属性上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: '权限组（角色）与菜单管理。')]
#[RouteGroup(prefix: '/api/v1', name: 'staff-role')]
interface StaffRoleApiInterface
{
    /**
     * 分页查询角色
     * @param ListRolesRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ListRolesResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('分页查询角色')]
    #[Route(method: 'GET', path: '/roles')]
    public function listRoles(ListRolesRequest $request): ListRolesResponse;

    /**
     * 角色统计
     * @return RoleStatsResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('角色统计')]
    #[Route(method: 'GET', path: '/roles/stats')]
    public function roleStats(): RoleStatsResponse;

    /**
     * 角色下拉选项
     * @return RoleOptionsResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('角色下拉选项')]
    #[Route(method: 'GET', path: '/roles/options')]
    public function listRoleOptions(): RoleOptionsResponse;

    /**
     * 创建角色
     * @param StaffRoleCreateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffRoleRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('创建角色')]
    #[Route(method: 'POST', path: '/roles')]
    public function createRole(StaffRoleCreateRequest $request): StaffRoleRowResponse;

    /**
     * 更新角色
     * @param StaffRoleUpdateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffRoleRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('更新角色')]
    #[Route(method: 'PUT', path: '/roles')]
    public function updateRole(StaffRoleUpdateRequest $request): StaffRoleRowResponse;

    /**
     * 角色详情
     * @param StaffRoleIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffRoleRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('角色详情')]
    #[Route(method: 'GET', path: '/roles/detail')]
    public function getRole(StaffRoleIdRequest $request): StaffRoleRowResponse;

    /**
     * 删除角色
     * @param StaffRoleIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffDeleteAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('删除角色')]
    #[Route(method: 'DELETE', path: '/roles')]
    public function deleteRole(StaffRoleIdRequest $request): StaffDeleteAckResponse;

    /**
     * 启用或禁用角色
     * @param StaffRoleStatusRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffRoleStatusAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('启用或禁用角色')]
    #[Route(method: 'POST', path: '/roles/status')]
    public function switchStatus(StaffRoleStatusRequest $request): StaffRoleStatusAckResponse;

    /**
     * 配置角色菜单页面权限
     * @param StaffRolePagesRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffRoleRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('配置角色菜单页面权限')]
    #[Route(method: 'POST', path: '/roles/pages')]
    public function grantRolePages(StaffRolePagesRequest $request): StaffRoleRowResponse;

    /**
     * 菜单树
     * @return StaffMenuTreeResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('菜单树')]
    #[Route(method: 'GET', path: '/menus')]
    public function listMenus(): StaffMenuTreeResponse;

    /**
     * 创建菜单
     * @param StaffMenuCreateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffMenuRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('创建菜单')]
    #[Route(method: 'POST', path: '/menus')]
    public function createMenu(StaffMenuCreateRequest $request): StaffMenuRowResponse;

    /**
     * 更新菜单
     * @param StaffMenuUpdateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffMenuRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('更新菜单')]
    #[Route(method: 'PUT', path: '/menus')]
    public function updateMenu(StaffMenuUpdateRequest $request): StaffMenuRowResponse;

    /**
     * 启用或禁用菜单
     * @param StaffMenuStatusRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffMenuStatusAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('启用或禁用菜单')]
    #[Route(method: 'POST', path: '/menus/status')]
    public function switchMenuStatus(StaffMenuStatusRequest $request): StaffMenuStatusAckResponse;

    /**
     * 同级菜单排序
     * @param StaffMenuSortRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffMenuSortAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('同级菜单排序')]
    #[Route(method: 'POST', path: '/menus/sort')]
    public function sortMenus(StaffMenuSortRequest $request): StaffMenuSortAckResponse;

    /**
     * 菜单详情
     * @param StaffMenuIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffMenuRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('菜单详情')]
    #[Route(method: 'GET', path: '/menus/detail')]
    public function getMenu(StaffMenuIdRequest $request): StaffMenuRowResponse;

    /**
     * 删除菜单
     * @param StaffMenuIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffDeleteAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('删除菜单')]
    #[Route(method: 'DELETE', path: '/menus')]
    public function deleteMenu(StaffMenuIdRequest $request): StaffDeleteAckResponse;
}
