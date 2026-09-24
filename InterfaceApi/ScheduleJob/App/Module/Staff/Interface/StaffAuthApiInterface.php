<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface;

use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\ChangePasswordRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\LoginRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\UpdateProfileRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\AuthMeResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ChangePasswordAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\LoginResponse;
use InterfaceApi\Support\ApiController;
use InterfaceApi\Support\ApiOperation;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

/**
 * 登录 / 当前用户（公开注册已关闭，用户由管理员在后台创建）。
 *
 * 入参 / 出参字段定义见各 Request、Response 及其 DTO 属性上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: '登录 / 当前用户（公开注册已关闭，用户由管理员在后台创建）。')]
#[RouteGroup(prefix: '/api/v1', name: 'staff-auth')]
interface StaffAuthApiInterface
{
    /**
     * 用户登录
     * @param LoginRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return LoginResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('用户登录')]
    #[Route(method: 'POST', path: '/auth/login')]
    public function login(LoginRequest $request): LoginResponse;

    /**
     * 当前登录用户
     * @return AuthMeResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('当前登录用户')]
    #[Route(method: 'GET', path: '/auth/me')]
    public function me(): AuthMeResponse;

    /**
     * 修改当前用户密码
     * @param ChangePasswordRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ChangePasswordAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('修改当前用户密码')]
    #[Route(method: 'POST', path: '/auth/password')]
    public function changePassword(ChangePasswordRequest $request): ChangePasswordAckResponse;

    /**
     * 修改当前用户资料
     * @param UpdateProfileRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return AuthMeResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('修改当前用户资料')]
    #[Route(method: 'POST', path: '/auth/profile')]
    public function updateProfile(UpdateProfileRequest $request): AuthMeResponse;
}
