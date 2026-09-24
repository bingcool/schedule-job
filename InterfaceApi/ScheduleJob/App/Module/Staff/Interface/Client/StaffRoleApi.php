<?php

declare(strict_types=1);

// @generated

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface\Client;

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
use InterfaceApi\Support\BaseClientApi;
use InterfaceApi\Support\CovertProperty;

class StaffRoleApi extends BaseClientApi
{
    protected string $serviceName = 'schedule-job';

    /**
     * 分页查询角色
     */
    public function listRoles(ListRolesRequest $request, array $options = []): ListRolesResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/roles'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ListRolesResponse::class);
    }

    /**
     * 角色统计
     */
    public function roleStats(array $options = []): RoleStatsResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/roles/stats'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, RoleStatsResponse::class);
    }

    /**
     * 角色下拉选项
     */
    public function listRoleOptions(array $options = []): RoleOptionsResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/roles/options'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, RoleOptionsResponse::class);
    }

    /**
     * 创建角色
     */
    public function createRole(StaffRoleCreateRequest $request, array $options = []): StaffRoleRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/roles'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffRoleRowResponse::class);
    }

    /**
     * 更新角色
     */
    public function updateRole(StaffRoleUpdateRequest $request, array $options = []): StaffRoleRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/roles'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffRoleRowResponse::class);
    }

    /**
     * 角色详情
     */
    public function getRole(StaffRoleIdRequest $request, array $options = []): StaffRoleRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/roles/detail'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffRoleRowResponse::class);
    }

    /**
     * 删除角色
     */
    public function deleteRole(StaffRoleIdRequest $request, array $options = []): StaffDeleteAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('DELETE', $this->uri('/api/v1/roles'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffDeleteAckResponse::class);
    }

    /**
     * 启用或禁用角色
     */
    public function switchStatus(StaffRoleStatusRequest $request, array $options = []): StaffRoleStatusAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/roles/status'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffRoleStatusAckResponse::class);
    }

    /**
     * 配置角色菜单页面权限
     */
    public function grantRolePages(StaffRolePagesRequest $request, array $options = []): StaffRoleRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/roles/pages'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffRoleRowResponse::class);
    }

    /**
     * 菜单树
     */
    public function listMenus(array $options = []): StaffMenuTreeResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/menus'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffMenuTreeResponse::class);
    }

    /**
     * 创建菜单
     */
    public function createMenu(StaffMenuCreateRequest $request, array $options = []): StaffMenuRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/menus'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffMenuRowResponse::class);
    }

    /**
     * 更新菜单
     */
    public function updateMenu(StaffMenuUpdateRequest $request, array $options = []): StaffMenuRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/menus'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffMenuRowResponse::class);
    }

    /**
     * 启用或禁用菜单
     */
    public function switchMenuStatus(StaffMenuStatusRequest $request, array $options = []): StaffMenuStatusAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/menus/status'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffMenuStatusAckResponse::class);
    }

    /**
     * 同级菜单排序
     */
    public function sortMenus(StaffMenuSortRequest $request, array $options = []): StaffMenuSortAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/menus/sort'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffMenuSortAckResponse::class);
    }

    /**
     * 菜单详情
     */
    public function getMenu(StaffMenuIdRequest $request, array $options = []): StaffMenuRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/menus/detail'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffMenuRowResponse::class);
    }

    /**
     * 删除菜单
     */
    public function deleteMenu(StaffMenuIdRequest $request, array $options = []): StaffDeleteAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('DELETE', $this->uri('/api/v1/menus'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, StaffDeleteAckResponse::class);
    }
}
