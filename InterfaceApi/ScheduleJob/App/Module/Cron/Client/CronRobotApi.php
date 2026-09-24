<?php

declare(strict_types=1);

// @generated

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Client;

use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotTestResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronDeleteAckResponse;
use InterfaceApi\Support\BaseClientApi;
use InterfaceApi\Support\CovertProperty;

class CronRobotApi extends BaseClientApi
{
    protected string $serviceName = 'schedule-job';

    /**
     * 机器人列表
     */
    public function listRobots(array $options = []): CronRobotListResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/robots'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronRobotListResponse::class);
    }

    /**
     * 机器人详情（脱敏）
     */
    public function getRobot(CronRobotIdRequest $request, array $options = []): CronRobotRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/robots/detail'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronRobotRowResponse::class);
    }

    /**
     * 创建机器人
     */
    public function createRobot(CronRobotCreateRequest $request, array $options = []): CronRobotRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/robots'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronRobotRowResponse::class);
    }

    /**
     * 更新机器人
     */
    public function updateRobot(CronRobotUpdateRequest $request, array $options = []): CronRobotRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/robots'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronRobotRowResponse::class);
    }

    /**
     * 删除机器人
     */
    public function deleteRobot(CronRobotIdRequest $request, array $options = []): CronDeleteAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('DELETE', $this->uri('/api/v1/robots'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronDeleteAckResponse::class);
    }

    /**
     * 启用或禁用机器人
     */
    public function switchStatus(CronRobotStatusRequest $request, array $options = []): CronRobotRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/robots/status'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronRobotRowResponse::class);
    }

    /**
     * 发送机器人连通测试
     */
    public function testRobot(CronRobotIdRequest $request, array $options = []): CronRobotTestResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/robots/test'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronRobotTestResponse::class);
    }
}
