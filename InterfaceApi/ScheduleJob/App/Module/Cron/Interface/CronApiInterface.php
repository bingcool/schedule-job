<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Interface;

use InterfaceApi\Support\ApiController;

/**
 * Cron 模块 HTTP 契约汇总（生成 Client 时可扫描本接口以包含全部路由）。
 *
 * 各 {@see CronTaskManagerApiInterface}、{@see CronRobotApiInterface} 由对应 Controller 实现。
 * 字段级说明见各 Request / Response / DTO 上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: 'Cron 模块 API（任务、节点、机器人、Agent）')]
interface CronApiInterface extends CronTaskManagerApiInterface, CronRobotApiInterface
{
}
