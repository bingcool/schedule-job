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
use InterfaceApi\Support\ApiController;
use InterfaceApi\Support\ApiOperation;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

/**
 * 用户管理 —— Request ↔ DTO / Response 映射，业务在 {
 *
 * 入参 / 出参字段定义见各 Request、Response 及其 DTO 属性上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: '用户管理 —— Request ↔ DTO / Response 映射，业务在 {')]
#[RouteGroup(prefix: '/api/v1', name: 'staff-user')]
interface StaffUserApiInterface
{
    /**
     * 分页查询用户
     * @param ListUsersRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ListUsersResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('分页查询用户')]
    #[Route(method: 'GET', path: '/users')]
    public function listUsers(ListUsersRequest $request): ListUsersResponse;

    /**
     * 创建用户
     * @param StaffUserCreateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffUserRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('创建用户')]
    #[Route(method: 'POST', path: '/users')]
    public function createUser(StaffUserCreateRequest $request): StaffUserRowResponse;

    /**
     * 更新用户
     * @param StaffUserUpdateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffUserRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('更新用户')]
    #[Route(method: 'PUT', path: '/users')]
    public function updateUser(StaffUserUpdateRequest $request): StaffUserRowResponse;

    /**
     * 用户详情
     * @param StaffUserIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffUserRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('用户详情')]
    #[Route(method: 'GET', path: '/users/detail')]
    public function getUser(StaffUserIdRequest $request): StaffUserRowResponse;

    /**
     * 删除用户
     * @param StaffUserIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffDeleteAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('删除用户')]
    #[Route(method: 'DELETE', path: '/users')]
    public function deleteUser(StaffUserIdRequest $request): StaffDeleteAckResponse;

    /**
     * 启用或禁用用户
     * @param StaffUserStatusRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffUserStatusAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('启用或禁用用户')]
    #[Route(method: 'POST', path: '/users/status')]
    public function switchStatus(StaffUserStatusRequest $request): StaffUserStatusAckResponse;

    /**
     * 生成临时重置密码
     * @param StaffUserGenerateResetPasswordRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return GenerateResetPasswordResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('生成临时重置密码')]
    #[Route(method: 'POST', path: '/users/generate-reset-password')]
    public function generateResetPassword(StaffUserGenerateResetPasswordRequest $request): GenerateResetPasswordResponse;

    /**
     * 超级管理员重置用户密码
     * @param StaffUserResetPasswordRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ResetPasswordAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('超级管理员重置用户密码')]
    #[Route(method: 'POST', path: '/users/reset-password')]
    public function resetPassword(StaffUserResetPasswordRequest $request): ResetPasswordAckResponse;

    /**
     * 分配用户角色
     * @param StaffUserRolesRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffUserRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('分配用户角色')]
    #[Route(method: 'POST', path: '/users/roles')]
    public function grantRoles(StaffUserRolesRequest $request): StaffUserRowResponse;

    /**
     * 授权用户节点组
     * @param StaffUserNodeGroupsRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffUserRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('授权用户节点组')]
    #[Route(method: 'POST', path: '/users/node-groups')]
    public function grantNodeGroups(StaffUserNodeGroupsRequest $request): StaffUserRowResponse;

    /**
     * 按节点分组查询可授权用户
     * @param StaffUserByNodeGroupRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return StaffUserBriefListResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('按节点分组查询可授权用户')]
    #[Route(method: 'GET', path: '/users/by-node-group')]
    public function listUsersByNodeGroup(StaffUserByNodeGroupRequest $request): StaffUserBriefListResponse;
}
