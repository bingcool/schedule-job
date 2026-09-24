<?php

declare(strict_types=1);

// @generated

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface\Client;

use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\ChangePasswordRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\LoginRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\UpdateProfileRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\AuthMeResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ChangePasswordAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\LoginResponse;
use InterfaceApi\Support\BaseClientApi;
use InterfaceApi\Support\CovertProperty;

class StaffAuthApi extends BaseClientApi
{
    protected string $serviceName = 'schedule-job';

    /**
     * 用户登录
     */
    public function login(LoginRequest $request, array $options = []): LoginResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/auth/login'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, LoginResponse::class);
    }

    /**
     * 当前登录用户
     */
    public function me(array $options = []): AuthMeResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/auth/me'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, AuthMeResponse::class);
    }

    /**
     * 修改当前用户密码
     */
    public function changePassword(ChangePasswordRequest $request, array $options = []): ChangePasswordAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/auth/password'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ChangePasswordAckResponse::class);
    }

    /**
     * 修改当前用户资料
     */
    public function updateProfile(UpdateProfileRequest $request, array $options = []): AuthMeResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/auth/profile'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, AuthMeResponse::class);
    }
}
