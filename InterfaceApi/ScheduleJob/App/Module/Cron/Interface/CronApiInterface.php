<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Interface;

/**
 * Cron 模块契约汇总（生成 Client 时可扫描本接口以包含全部路由）。
 * 各 {@see CronTaskManagerApiInterface}、{@see CronRobotApiInterface} 由对应 Controller 实现。
 */
interface CronApiInterface extends CronTaskManagerApiInterface, CronRobotApiInterface
{
}
