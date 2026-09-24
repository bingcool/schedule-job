<?php

declare(strict_types=1);

namespace App\Module\Staff\Controller;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\CreateUserDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\GrantUserNodeGroupsDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\GrantUserRolesDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\ListUsersQueryDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\ResetUserPasswordDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\SwitchUserStatusDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\UpdateUserDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\UserIdDto;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\ListUsersRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserByNodeGroupRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserNodeGroupsRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserGenerateResetPasswordRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserResetPasswordRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserRolesRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\StaffUserUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\GenerateResetPasswordResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ListUsersResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ResetPasswordAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffUserBriefListResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffDeleteAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffUserRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\StaffUserStatusAckResponse;
use App\Module\Staff\Service\StaffUserService;
use Swoolefy\Annotation\ApiOperation;
use Swoolefy\Core\Controller\BController;

/**
 * 用户管理 —— Request ↔ DTO / Response 映射，业务在 {@see StaffUserService}。
 */
class StaffUserController extends BController
{
    private StaffUserService $staffUserService {
        get => $this->staffUserService ??= new StaffUserService();
    }

    /**
     * Route: GET /api/v1/users
     *
     ```bash
     curl -X GET 'http://127.0.0.1:9501/api/v1/users?page=1&pageSize=20' \
       -H 'Authorization: Bearer <jwt>'
     ```
     */
    #[ApiOperation('分页查询用户')]
    public function listUsers(ListUsersRequest $request): ListUsersResponse
    {
        $query = (new ListUsersQueryDto())
            ->setPage($request->getPage())
            ->setPageSize($request->getPageSize())
            ->setAccount($request->getAccount())
            ->setUserName($request->getUserName())
            ->setStatus($request->getStatus());

        return new ListUsersResponse($this->staffUserService->listUsers($query));
    }

    /**
     * Route: POST /api/v1/users
     *
     ```bash
     curl -X POST 'http://127.0.0.1:9501/api/v1/users' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"account":"ops@example.com","userName":"运维","password":"12345678"}'
     ```
     */
    #[ApiOperation('创建用户')]
    public function createUser(StaffUserCreateRequest $request): StaffUserRowResponse
    {
        $dto = (new CreateUserDto())
            ->setAccount($request->getAccount())
            ->setEmail($request->getEmail())
            ->setUserName($request->getUserName())
            ->setPassword($request->getPassword());

        return new StaffUserRowResponse($this->staffUserService->createUser($dto));
    }

    /**
     * Route: PUT /api/v1/users
     */
    #[ApiOperation('更新用户')]
    public function updateUser(StaffUserUpdateRequest $request): StaffUserRowResponse
    {
        $dto = (new UpdateUserDto())
            ->setId($request->getId())
            ->setAccount($request->getAccount())
            ->setEmail($request->getEmail())
            ->setUserName($request->getUserName());

        return new StaffUserRowResponse($this->staffUserService->updateUser($dto));
    }

    /**
     * Route: GET /api/v1/users/detail?id=
     */
    #[ApiOperation('用户详情')]
    public function getUser(StaffUserIdRequest $request): StaffUserRowResponse
    {
        return new StaffUserRowResponse($this->staffUserService->getUser(UserIdDto::of($request->getId())));
    }

    /**
     * Route: DELETE /api/v1/users
     */
    #[ApiOperation('删除用户')]
    public function deleteUser(StaffUserIdRequest $request): StaffDeleteAckResponse
    {
        $id = $this->staffUserService->deleteUser(UserIdDto::of($request->getId()));

        return new StaffDeleteAckResponse($id);
    }

    /**
     * 启用或禁用用户（status：1=启用，0=禁用）。
     *
     * Route: PUT /api/v1/users/status
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9501/api/v1/users/status' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"id": 2, "status": 0}'
     ```
     */
    #[ApiOperation('启用或禁用用户')]
    public function switchStatus(StaffUserStatusRequest $request): StaffUserStatusAckResponse
    {
        $ack = $this->staffUserService->switchStatus(
            SwitchUserStatusDto::of($request->getId(), $request->getStatus())
        );

        return new StaffUserStatusAckResponse($ack->getId(), $ack->getStatus());
    }

    /**
     * 超级管理员生成 3 天有效的 32 位临时重置密码。
     *
     * Route: POST /api/v1/users/generate-reset-password
     *
     ```bash
     curl -X POST 'http://127.0.0.1:9502/api/v1/users/generate-reset-password' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"userId":2}'
     ```
     */
    #[ApiOperation('生成临时重置密码')]
    public function generateResetPassword(StaffUserGenerateResetPasswordRequest $request): GenerateResetPasswordResponse
    {
        return new GenerateResetPasswordResponse(
            $this->staffUserService->generateResetPassword($request->getUserId())
        );
    }

    /**
     * 超级管理员确认写入已生成的临时重置密码。
     *
     * Route: PUT /api/v1/users/reset-password
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9502/api/v1/users/reset-password' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"id":2,"password":"<32-char-password>"}'
     ```
     */
    #[ApiOperation('超级管理员重置用户密码')]
    public function resetPassword(StaffUserResetPasswordRequest $request): ResetPasswordAckResponse
    {
        return new ResetPasswordAckResponse(
            $this->staffUserService->resetPasswordBySuperAdmin(
                ResetUserPasswordDto::of($request->getId(), $request->getPassword())
            )
        );
    }

    /**
     * 独立分配用户角色。
     *
     * Route: PUT /api/v1/users/roles
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9501/api/v1/users/roles' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"id": 2, "roleIds": [1, 3]}'
     ```
     */
    #[ApiOperation('分配用户角色')]
    public function grantRoles(StaffUserRolesRequest $request): StaffUserRowResponse
    {
        return new StaffUserRowResponse($this->staffUserService->grantRoles(
            GrantUserRolesDto::of($request->getId(), $request->getRoleIds())
        ));
    }

    /**
     * 独立授权用户可管理的节点组。
     *
     * Route: PUT /api/v1/users/node-groups
     *
     ```bash
     curl -X PUT 'http://127.0.0.1:9501/api/v1/users/node-groups' \
       -H 'Authorization: Bearer <jwt>' \
       -H 'Content-Type: application/json' \
       -d '{"id": 2, "nodeGroupIds": [1, 3]}'
     ```
     */
    #[ApiOperation('授权用户节点组')]
    public function grantNodeGroups(StaffUserNodeGroupsRequest $request): StaffUserRowResponse
    {
        return new StaffUserRowResponse($this->staffUserService->grantNodeGroups(
            GrantUserNodeGroupsDto::of($request->getId(), $request->getNodeGroupIds())
        ));
    }

    /**
     * Route: GET /api/v1/users/by-node-group?nodeGroupId=
     */
    #[ApiOperation('按节点分组查询可授权用户')]
    public function listUsersByNodeGroup(StaffUserByNodeGroupRequest $request): StaffUserBriefListResponse
    {
        return new StaffUserBriefListResponse(
            $this->staffUserService->listUsersByNodeGroup($request->getNodeGroupId())
        );
    }
}
