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
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

#[RouteGroup(prefix: '/api/v1', name: 'staff-role')]
interface StaffRoleApiInterface
{
    #[Route(method: 'GET', path: '/roles')]
    public function listRoles(ListRolesRequest $request): ListRolesResponse;

    #[Route(method: 'GET', path: '/roles/stats')]
    public function roleStats(): RoleStatsResponse;

    #[Route(method: 'GET', path: '/roles/options')]
    public function listRoleOptions(): RoleOptionsResponse;

    #[Route(method: 'POST', path: '/roles')]
    public function createRole(StaffRoleCreateRequest $request): StaffRoleRowResponse;

    #[Route(method: 'PUT', path: '/roles')]
    public function updateRole(StaffRoleUpdateRequest $request): StaffRoleRowResponse;

    #[Route(method: 'GET', path: '/roles/detail')]
    public function getRole(StaffRoleIdRequest $request): StaffRoleRowResponse;

    #[Route(method: 'DELETE', path: '/roles')]
    public function deleteRole(StaffRoleIdRequest $request): StaffDeleteAckResponse;

    #[Route(method: 'POST', path: '/roles/status')]
    public function switchStatus(StaffRoleStatusRequest $request): StaffRoleStatusAckResponse;

    #[Route(method: 'POST', path: '/roles/pages')]
    public function grantRolePages(StaffRolePagesRequest $request): StaffRoleRowResponse;

    #[Route(method: 'GET', path: '/menus')]
    public function listMenus(): StaffMenuTreeResponse;

    #[Route(method: 'POST', path: '/menus')]
    public function createMenu(StaffMenuCreateRequest $request): StaffMenuRowResponse;

    #[Route(method: 'PUT', path: '/menus')]
    public function updateMenu(StaffMenuUpdateRequest $request): StaffMenuRowResponse;

    #[Route(method: 'POST', path: '/menus/status')]
    public function switchMenuStatus(StaffMenuStatusRequest $request): StaffMenuStatusAckResponse;

    #[Route(method: 'POST', path: '/menus/sort')]
    public function sortMenus(StaffMenuSortRequest $request): StaffMenuSortAckResponse;

    #[Route(method: 'GET', path: '/menus/detail')]
    public function getMenu(StaffMenuIdRequest $request): StaffMenuRowResponse;

    #[Route(method: 'DELETE', path: '/menus')]
    public function deleteMenu(StaffMenuIdRequest $request): StaffDeleteAckResponse;
}
