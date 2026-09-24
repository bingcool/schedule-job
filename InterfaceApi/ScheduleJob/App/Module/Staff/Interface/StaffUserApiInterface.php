<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface;

use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\ListUsersRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserByNodeGroupRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserGenerateResetPasswordRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserNodeGroupsRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserResetPasswordRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserRolesRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\GenerateResetPasswordResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ListUsersResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ResetPasswordAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffDeleteAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffUserBriefListResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffUserRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffUserStatusAckResponse;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

#[RouteGroup(prefix: '/api/v1', name: 'staff-user')]
interface StaffUserApiInterface
{
    #[Route(method: 'GET', path: '/users')]
    public function listUsers(ListUsersRequest $request): ListUsersResponse;

    #[Route(method: 'POST', path: '/users')]
    public function createUser(StaffUserCreateRequest $request): StaffUserRowResponse;

    #[Route(method: 'PUT', path: '/users')]
    public function updateUser(StaffUserUpdateRequest $request): StaffUserRowResponse;

    #[Route(method: 'GET', path: '/users/detail')]
    public function getUser(StaffUserIdRequest $request): StaffUserRowResponse;

    #[Route(method: 'DELETE', path: '/users')]
    public function deleteUser(StaffUserIdRequest $request): StaffDeleteAckResponse;

    #[Route(method: 'POST', path: '/users/status')]
    public function switchStatus(StaffUserStatusRequest $request): StaffUserStatusAckResponse;

    #[Route(method: 'POST', path: '/users/generate-reset-password')]
    public function generateResetPassword(StaffUserGenerateResetPasswordRequest $request): GenerateResetPasswordResponse;

    #[Route(method: 'POST', path: '/users/reset-password')]
    public function resetPassword(StaffUserResetPasswordRequest $request): ResetPasswordAckResponse;

    #[Route(method: 'POST', path: '/users/roles')]
    public function grantRoles(StaffUserRolesRequest $request): StaffUserRowResponse;

    #[Route(method: 'POST', path: '/users/node-groups')]
    public function grantNodeGroups(StaffUserNodeGroupsRequest $request): StaffUserRowResponse;

    #[Route(method: 'GET', path: '/users/by-node-group')]
    public function listUsersByNodeGroup(StaffUserByNodeGroupRequest $request): StaffUserBriefListResponse;
}
