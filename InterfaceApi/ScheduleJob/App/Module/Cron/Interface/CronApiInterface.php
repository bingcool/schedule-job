<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Interface;

use InterfaceApi\Support\ApiController;
use InterfaceApi\Support\RouteGroup;

/**
 * Cron 模块 HTTP 契约汇总（extends 子接口；生成 `CronApi` Client 含全部路由，子接口另各有 Client）。
 *
 * 各 {@see CronTaskManagerApiInterface}、{@see CronRobotApiInterface} 由对应 Controller 实现。
 * 字段级说明见各 Request / Response / DTO 上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: 'Cron 模块 API（任务、节点、机器人、Agent）')]
#[RouteGroup(prefix: '/api/v1', name: 'cron-module')]
interface CronApiInterface extends CronTaskManagerApiInterface, CronRobotApiInterface
{
}
