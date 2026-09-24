<?php

declare(strict_types=1);

// @generated

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Client;

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
use InterfaceApi\Support\BaseClientApi;
use InterfaceApi\Support\CovertProperty;

class StaffUserApi extends BaseClientApi
{
    protected string $serviceName = 'schedule-job';

    /**
     * 分页查询用户
     */
    public function listUsers(ListUsersRequest $request, array $options = []): ListUsersResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/users'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ListUsersResponse::class);
    }

    /**
     * 创建用户
     */
    public function createUser(StaffUserCreateRequest $request, array $options = []): StaffUserRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/users'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffUserRowResponse::class);
    }

    /**
     * 更新用户
     */
    public function updateUser(StaffUserUpdateRequest $request, array $options = []): StaffUserRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/users'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffUserRowResponse::class);
    }

    /**
     * 用户详情
     */
    public function getUser(StaffUserIdRequest $request, array $options = []): StaffUserRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/users/detail'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffUserRowResponse::class);
    }

    /**
     * 删除用户
     */
    public function deleteUser(StaffUserIdRequest $request, array $options = []): StaffDeleteAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('DELETE', $this->uri('/api/v1/users'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffDeleteAckResponse::class);
    }

    /**
     * 启用或禁用用户
     */
    public function switchStatus(StaffUserStatusRequest $request, array $options = []): StaffUserStatusAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/users/status'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffUserStatusAckResponse::class);
    }

    /**
     * 生成临时重置密码
     */
    public function generateResetPassword(StaffUserGenerateResetPasswordRequest $request, array $options = []): GenerateResetPasswordResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/users/generate-reset-password'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, GenerateResetPasswordResponse::class);
    }

    /**
     * 超级管理员重置用户密码
     */
    public function resetPassword(StaffUserResetPasswordRequest $request, array $options = []): ResetPasswordAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/users/reset-password'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ResetPasswordAckResponse::class);
    }

    /**
     * 分配用户角色
     */
    public function grantRoles(StaffUserRolesRequest $request, array $options = []): StaffUserRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/users/roles'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffUserRowResponse::class);
    }

    /**
     * 授权用户节点组
     */
    public function grantNodeGroups(StaffUserNodeGroupsRequest $request, array $options = []): StaffUserRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/users/node-groups'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffUserRowResponse::class);
    }

    /**
     * 按节点分组查询可授权用户
     */
    public function listUsersByNodeGroup(StaffUserByNodeGroupRequest $request, array $options = []): StaffUserBriefListResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/users/by-node-group'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffUserBriefListResponse::class);
    }
}
