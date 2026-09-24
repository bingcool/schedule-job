<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Interface;

use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotTestResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronDeleteAckResponse;
use InterfaceApi\Support\ApiController;
use InterfaceApi\Support\ApiOperation;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

/**
 * 告警机器人 Webhook 配置 API
 *
 * 入参 / 出参字段定义见各 Request、Response 及其 DTO 属性上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: '告警机器人 Webhook 配置 API')]
#[RouteGroup(prefix: '/api/v1', name: 'cron-robot')]
interface CronRobotApiInterface
{
    /**
     * 机器人列表
     * @return CronRobotListResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('机器人列表')]
    #[Route(method: 'GET', path: '/robots')]
    public function listRobots(): CronRobotListResponse;

    /**
     * 机器人详情（脱敏）
     * @param CronRobotIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronRobotRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('机器人详情（脱敏）')]
    #[Route(method: 'GET', path: '/robots/detail')]
    public function getRobot(CronRobotIdRequest $request): CronRobotRowResponse;

    /**
     * 创建机器人
     * @param CronRobotCreateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronRobotRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('创建机器人')]
    #[Route(method: 'POST', path: '/robots')]
    public function createRobot(CronRobotCreateRequest $request): CronRobotRowResponse;

    /**
     * 更新机器人
     * @param CronRobotUpdateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronRobotRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('更新机器人')]
    #[Route(method: 'PUT', path: '/robots')]
    public function updateRobot(CronRobotUpdateRequest $request): CronRobotRowResponse;

    /**
     * 删除机器人
     * @param CronRobotIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronDeleteAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('删除机器人')]
    #[Route(method: 'DELETE', path: '/robots')]
    public function deleteRobot(CronRobotIdRequest $request): CronDeleteAckResponse;

    /**
     * 启用或禁用机器人
     * @param CronRobotStatusRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronRobotRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('启用或禁用机器人')]
    #[Route(method: 'POST', path: '/robots/status')]
    public function switchStatus(CronRobotStatusRequest $request): CronRobotRowResponse;

    /**
     * 发送机器人连通测试
     * @param CronRobotIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronRobotTestResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('发送机器人连通测试')]
    #[Route(method: 'POST', path: '/robots/test')]
    public function testRobot(CronRobotIdRequest $request): CronRobotTestResponse;
}
