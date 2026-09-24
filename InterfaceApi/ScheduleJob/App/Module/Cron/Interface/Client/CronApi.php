<?php

declare(strict_types=1);

// @generated

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Interface\Client;

use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot\CronRobotUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\BatchStatusRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronAgentHeartbeatRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronAgentReportRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronAgentTasksQueryRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronNodeCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronNodeGroupCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronNodeGroupIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronNodeGroupUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronNodeIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronNodeUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronTaskCreateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronTaskIdRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronTaskStatsQueryRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronTaskStatusSwitchRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\CronTaskUpdateRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\DashboardTrendRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\ExecutionCancelRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\ExecutionDetailRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\ExpressionPreviewRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\ListTasksRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\TaskLogsQueryRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\TaskLogsTrendRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\TaskOperationLogsQueryRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager\TaskTransferOwnerRequest;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot\CronRobotTestResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\BatchStatusResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronAgentHeartbeatResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronAgentReportAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronAgentTasksResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronDeleteAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronNodeCreateResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronNodeGroupListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronNodeGroupRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronNodeListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronNodeRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronTaskRowResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronTaskStatsResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronTaskStatusAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\DashboardOverviewResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\ExecutionCancelResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\ExecutionDetailResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\ExecutionTrendResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\ExpressionPreviewResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\ListTasksResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\RunOnceQueuedResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\RuntimeOverviewResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\TaskCreatorOptionsResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\TaskLogsResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\TaskOperationLogsResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\TaskOperationOperatorOptionsResponse;
use InterfaceApi\Support\BaseClientApi;
use InterfaceApi\Support\CovertProperty;

class CronApi extends BaseClientApi
{
    protected string $serviceName = 'schedule-job';

    /**
     * 分页查询定时任务列表
     */
    public function listTasks(ListTasksRequest $request, array $options = []): ListTasksResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ListTasksResponse::class);
    }

    /**
     * 计划任务创建人下拉选项
     */
    public function listTaskCreators(array $options = []): TaskCreatorOptionsResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/creators'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, TaskCreatorOptionsResponse::class);
    }

    /**
     * 创建定时任务
     */
    public function createTask(CronTaskCreateRequest $request, array $options = []): CronTaskRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/tasks'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronTaskRowResponse::class);
    }

    /**
     * 更新定时任务
     */
    public function updateTask(CronTaskUpdateRequest $request, array $options = []): CronTaskRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/tasks'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronTaskRowResponse::class);
    }

    /**
     * 删除定时任务
     */
    public function deleteTask(CronTaskIdRequest $request, array $options = []): CronDeleteAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('DELETE', $this->uri('/api/v1/tasks'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronDeleteAckResponse::class);
    }

    /**
     * 切换定时任务启用状态
     */
    public function switchTaskStatus(CronTaskStatusSwitchRequest $request, array $options = []): CronTaskStatusAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/tasks/status'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronTaskStatusAckResponse::class);
    }

    /**
     * 查询 Cron Agent 节点列表
     */
    public function listNodes(array $options = []): CronNodeListResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/nodes'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeListResponse::class);
    }

    /**
     * 创建 Cron Agent 节点
     */
    public function createNode(CronNodeCreateRequest $request, array $options = []): CronNodeCreateResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/nodes'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeCreateResponse::class);
    }

    /**
     * 删除 Cron Agent 节点
     */
    public function deleteNode(CronNodeIdRequest $request, array $options = []): CronDeleteAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('DELETE', $this->uri('/api/v1/nodes'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronDeleteAckResponse::class);
    }

    /**
     * 分页查询任务执行日志
     */
    public function taskLogs(TaskLogsQueryRequest $request, array $options = []): TaskLogsResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/logs'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, TaskLogsResponse::class);
    }

    /**
     * 执行记录趋势折线
     */
    public function taskLogsTrend(TaskLogsTrendRequest $request, array $options = []): ExecutionTrendResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/logs/trend'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ExecutionTrendResponse::class);
    }

    /**
     * 分页查询计划任务操作记录
     */
    public function taskOperationLogs(TaskOperationLogsQueryRequest $request, array $options = []): TaskOperationLogsResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/operation-logs'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, TaskOperationLogsResponse::class);
    }

    /**
     * 计划任务操作记录操作人选项
     */
    public function listTaskOperationOperators(array $options = []): TaskOperationOperatorOptionsResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/operation-logs/operators'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, TaskOperationOperatorOptionsResponse::class);
    }

    /**
     * 查询任务执行统计
     */
    public function taskStats(CronTaskStatsQueryRequest $request, array $options = []): CronTaskStatsResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/stats'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronTaskStatsResponse::class);
    }

    /**
     * Agent 拉取待执行的定时任务
     */
    public function agentTasks(CronAgentTasksQueryRequest $request, array $options = []): CronAgentTasksResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/agent/tasks'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronAgentTasksResponse::class);
    }

    /**
     * Agent 心跳上报
     */
    public function agentHeartbeat(CronAgentHeartbeatRequest $request, array $options = []): CronAgentHeartbeatResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/agent/heartbeat'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronAgentHeartbeatResponse::class);
    }

    /**
     * Agent 上报任务执行结果
     */
    public function agentReport(CronAgentReportRequest $request, array $options = []): CronAgentReportAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/agent/report'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronAgentReportAckResponse::class);
    }

    /**
     * 查询定时任务详情
     */
    public function getTask(CronTaskIdRequest $request, array $options = []): CronTaskRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/detail'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronTaskRowResponse::class);
    }

    /**
     * 预览 Cron 表达式
     */
    public function previewExpression(ExpressionPreviewRequest $request, array $options = []): ExpressionPreviewResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/tasks/expression/preview'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ExpressionPreviewResponse::class);
    }

    /**
     * 查询单次执行详情
     */
    public function getExecution(ExecutionDetailRequest $request, array $options = []): ExecutionDetailResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/tasks/execution'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ExecutionDetailResponse::class);
    }

    /**
     * 取消一次执行
     */
    public function cancelExecution(ExecutionCancelRequest $request, array $options = []): ExecutionCancelResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/executions/cancel'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ExecutionCancelResponse::class);
    }

    /**
     * Dashboard 概览
     */
    public function dashboardOverview(array $options = []): DashboardOverviewResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/dashboard/overview'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, DashboardOverviewResponse::class);
    }

    /**
     * 执行趋势
     */
    public function executionTrend(DashboardTrendRequest $request, array $options = []): ExecutionTrendResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/dashboard/execution-trend'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, ExecutionTrendResponse::class);
    }

    /**
     * Runtime 概览
     */
    public function runtimeOverview(array $options = []): RuntimeOverviewResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/runtime/overview'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, RuntimeOverviewResponse::class);
    }

    /**
     * 查询节点详情
     */
    public function getNode(CronNodeIdRequest $request, array $options = []): CronNodeRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/nodes/detail'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeRowResponse::class);
    }

    /**
     * 更新节点
     */
    public function updateNode(CronNodeUpdateRequest $request, array $options = []): CronNodeRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/nodes'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeRowResponse::class);
    }

    /**
     * 查询节点分组列表
     */
    public function listNodeGroups(array $options = []): CronNodeGroupListResponse
    {
        $requestDefaults = [];
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/node-groups'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeGroupListResponse::class);
    }

    /**
     * 创建节点分组
     */
    public function createNodeGroup(CronNodeGroupCreateRequest $request, array $options = []): CronNodeGroupRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/node-groups'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeGroupRowResponse::class);
    }

    /**
     * 更新节点分组
     */
    public function updateNodeGroup(CronNodeGroupUpdateRequest $request, array $options = []): CronNodeGroupRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/node-groups'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeGroupRowResponse::class);
    }

    /**
     * 查询节点分组详情
     */
    public function getNodeGroup(CronNodeGroupIdRequest $request, array $options = []): CronNodeGroupRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('GET', $this->uri('/api/v1/node-groups/detail'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronNodeGroupRowResponse::class);
    }

    /**
     * 删除节点分组
     */
    public function deleteNodeGroup(CronNodeGroupIdRequest $request, array $options = []): CronDeleteAckResponse
    {
        $requestDefaults = [];
        $requestDefaults['query'] = $request->toDeepArray();
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('DELETE', $this->uri('/api/v1/node-groups'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronDeleteAckResponse::class);
    }

    /**
     * 批量启停任务
     */
    public function batchSwitchStatus(BatchStatusRequest $request, array $options = []): BatchStatusResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('PUT', $this->uri('/api/v1/tasks/batch-status'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, BatchStatusResponse::class);
    }

    /**
     * 复制定时任务
     */
    public function duplicateTask(CronTaskIdRequest $request, array $options = []): CronTaskRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/tasks/duplicate'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronTaskRowResponse::class);
    }

    /**
     * 入队手动执行一次
     */
    public function runTaskOnce(CronTaskIdRequest $request, array $options = []): RunOnceQueuedResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/tasks/run'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, RunOnceQueuedResponse::class);
    }

    /**
     * 变更任务权限所属人
     */
    public function transferTaskOwner(TaskTransferOwnerRequest $request, array $options = []): CronTaskRowResponse
    {
        $requestDefaults = [];
        $requestDefaults['body'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $options = $this->mergeClientOptions($requestDefaults, $options);
        $response = $this->requestWithConnectRetry('POST', $this->uri('/api/v1/tasks/owner'), $options);
        $result = $this->parseResponseByHeaders($response);
        return CovertProperty::toCovertDeepProperty($result, CronTaskRowResponse::class);
    }

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
