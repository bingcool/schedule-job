<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface;

use InterfaceApi\Support\ApiController;
use InterfaceApi\Support\RouteGroup;

/**
 * Staff 模块 HTTP 契约汇总（无方法；Client 按 {@see StaffAuthApiInterface}、
 * {@see StaffUserApiInterface}、{@see StaffRoleApiInterface} 分别生成）。
 *
 * 字段说明见 DTO 上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: 'Staff 模块 API（认证、用户、角色与菜单）')]
#[RouteGroup(prefix: '/api/v1', name: 'staff-module')]
interface StaffApiInterface
{
}
