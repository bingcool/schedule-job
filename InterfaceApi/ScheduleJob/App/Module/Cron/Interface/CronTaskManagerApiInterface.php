<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Interface;

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
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

#[RouteGroup(prefix: '/api/v1', name: 'cron')]
interface CronTaskManagerApiInterface
{
    #[Route(method: 'GET', path: '/tasks')]
    public function listTasks(ListTasksRequest $request): ListTasksResponse;

    #[Route(method: 'GET', path: '/tasks/creators')]
    public function listTaskCreators(): TaskCreatorOptionsResponse;

    #[Route(method: 'POST', path: '/tasks')]
    public function createTask(CronTaskCreateRequest $request): CronTaskRowResponse;

    #[Route(method: 'PUT', path: '/tasks')]
    public function updateTask(CronTaskUpdateRequest $request): CronTaskRowResponse;

    #[Route(method: 'DELETE', path: '/tasks')]
    public function deleteTask(CronTaskIdRequest $request): CronDeleteAckResponse;

    #[Route(method: 'POST', path: '/tasks/status')]
    public function switchTaskStatus(CronTaskStatusSwitchRequest $request): CronTaskStatusAckResponse;

    #[Route(method: 'GET', path: '/nodes')]
    public function listNodes(): CronNodeListResponse;

    #[Route(method: 'POST', path: '/nodes')]
    public function createNode(CronNodeCreateRequest $request): CronNodeCreateResponse;

    #[Route(method: 'DELETE', path: '/nodes')]
    public function deleteNode(CronNodeIdRequest $request): CronDeleteAckResponse;

    #[Route(method: 'GET', path: '/tasks/logs')]
    public function taskLogs(TaskLogsQueryRequest $request): TaskLogsResponse;

    #[Route(method: 'GET', path: '/tasks/logs/trend')]
    public function taskLogsTrend(TaskLogsTrendRequest $request): ExecutionTrendResponse;

    #[Route(method: 'GET', path: '/tasks/operation-logs')]
    public function taskOperationLogs(TaskOperationLogsQueryRequest $request): TaskOperationLogsResponse;

    #[Route(method: 'GET', path: '/tasks/operation-logs/operators')]
    public function listTaskOperationOperators(): TaskOperationOperatorOptionsResponse;

    #[Route(method: 'GET', path: '/tasks/stats')]
    public function taskStats(CronTaskStatsQueryRequest $request): CronTaskStatsResponse;

    #[Route(method: 'GET', path: '/agent/tasks')]
    public function agentTasks(CronAgentTasksQueryRequest $request): CronAgentTasksResponse;

    #[Route(method: 'POST', path: '/agent/heartbeat')]
    public function agentHeartbeat(CronAgentHeartbeatRequest $request): CronAgentHeartbeatResponse;

    #[Route(method: 'POST', path: '/agent/report')]
    public function agentReport(CronAgentReportRequest $request): CronAgentReportAckResponse;

    #[Route(method: 'GET', path: '/tasks/detail')]
    public function getTask(CronTaskIdRequest $request): CronTaskRowResponse;

    #[Route(method: 'POST', path: '/tasks/expression/preview')]
    public function previewExpression(ExpressionPreviewRequest $request): ExpressionPreviewResponse;

    #[Route(method: 'GET', path: '/tasks/execution')]
    public function getExecution(ExecutionDetailRequest $request): ExecutionDetailResponse;

    #[Route(method: 'POST', path: '/executions/cancel')]
    public function cancelExecution(ExecutionCancelRequest $request): ExecutionCancelResponse;

    #[Route(method: 'GET', path: '/dashboard/overview')]
    public function dashboardOverview(): DashboardOverviewResponse;

    #[Route(method: 'GET', path: '/dashboard/execution-trend')]
    public function executionTrend(DashboardTrendRequest $request): ExecutionTrendResponse;

    #[Route(method: 'GET', path: '/runtime/overview')]
    public function runtimeOverview(): RuntimeOverviewResponse;

    #[Route(method: 'GET', path: '/nodes/detail')]
    public function getNode(CronNodeIdRequest $request): CronNodeRowResponse;

    #[Route(method: 'PUT', path: '/nodes')]
    public function updateNode(CronNodeUpdateRequest $request): CronNodeRowResponse;

    #[Route(method: 'GET', path: '/node-groups')]
    public function listNodeGroups(): CronNodeGroupListResponse;

    #[Route(method: 'POST', path: '/node-groups')]
    public function createNodeGroup(CronNodeGroupCreateRequest $request): CronNodeGroupRowResponse;

    #[Route(method: 'PUT', path: '/node-groups')]
    public function updateNodeGroup(CronNodeGroupUpdateRequest $request): CronNodeGroupRowResponse;

    #[Route(method: 'GET', path: '/node-groups/detail')]
    public function getNodeGroup(CronNodeGroupIdRequest $request): CronNodeGroupRowResponse;

    #[Route(method: 'DELETE', path: '/node-groups')]
    public function deleteNodeGroup(CronNodeGroupIdRequest $request): CronDeleteAckResponse;

    #[Route(method: 'PUT', path: '/tasks/batch-status')]
    public function batchSwitchStatus(BatchStatusRequest $request): BatchStatusResponse;

    #[Route(method: 'POST', path: '/tasks/duplicate')]
    public function duplicateTask(CronTaskIdRequest $request): CronTaskRowResponse;

    #[Route(method: 'POST', path: '/tasks/run')]
    public function runTaskOnce(CronTaskIdRequest $request): RunOnceQueuedResponse;

    #[Route(method: 'POST', path: '/tasks/owner')]
    public function transferTaskOwner(TaskTransferOwnerRequest $request): CronTaskRowResponse;
}
