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
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

#[RouteGroup(prefix: '/api/v1', name: 'cron-robot')]
interface CronRobotApiInterface
{
    #[Route(method: 'GET', path: '/robots')]
    public function listRobots(): CronRobotListResponse;

    #[Route(method: 'GET', path: '/robots/detail')]
    public function getRobot(CronRobotIdRequest $request): CronRobotRowResponse;

    #[Route(method: 'POST', path: '/robots')]
    public function createRobot(CronRobotCreateRequest $request): CronRobotRowResponse;

    #[Route(method: 'PUT', path: '/robots')]
    public function updateRobot(CronRobotUpdateRequest $request): CronRobotRowResponse;

    #[Route(method: 'DELETE', path: '/robots')]
    public function deleteRobot(CronRobotIdRequest $request): CronDeleteAckResponse;

    #[Route(method: 'POST', path: '/robots/status')]
    public function switchStatus(CronRobotStatusRequest $request): CronRobotRowResponse;

    #[Route(method: 'POST', path: '/robots/test')]
    public function testRobot(CronRobotIdRequest $request): CronRobotTestResponse;
}
