<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface;

/**
 * Staff 模块契约汇总（生成 Client 时可扫描本接口以包含全部路由）。
 * 各子接口由对应 Controller 实现。
 */
interface StaffApiInterface extends StaffAuthApiInterface, StaffUserApiInterface, StaffRoleApiInterface
{
}
