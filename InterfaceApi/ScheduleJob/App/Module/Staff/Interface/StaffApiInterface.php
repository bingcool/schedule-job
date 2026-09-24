<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface;

use InterfaceApi\Support\ApiController;

/**
 * Staff 模块 HTTP 契约汇总（生成 Client 时可扫描本接口以包含全部路由）。
 *
 * 子接口 {@see StaffAuthApiInterface}、{@see StaffUserApiInterface}、{@see StaffRoleApiInterface}
 * 由对应 Controller 实现。字段说明见 DTO 上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: 'Staff 模块 API（认证、用户、角色与菜单）')]
interface StaffApiInterface extends StaffAuthApiInterface, StaffUserApiInterface, StaffRoleApiInterface
{
}
