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
use InterfaceApi\Support\ApiController;
use InterfaceApi\Support\ApiOperation;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

/**
 * Cron 任务管理控制器 —— 仅做 Request ↔ DTO / Response 映射，业务在 {
 *
 * 入参 / 出参字段定义见各 Request、Response 及其 DTO 属性上的 {@see \InterfaceApi\Support\ApiProperty}。
 */
#[ApiController(description: 'Cron 任务管理控制器 —— 仅做 Request ↔ DTO / Response 映射，业务在 {')]
#[RouteGroup(prefix: '/api/v1', name: 'cron')]
interface CronTaskManagerApiInterface
{
    /**
     * 分页查询定时任务列表
     * @param ListTasksRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ListTasksResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('分页查询定时任务列表')]
    #[Route(method: 'GET', path: '/tasks')]
    public function listTasks(ListTasksRequest $request): ListTasksResponse;

    /**
     * 计划任务创建人下拉选项
     * @return TaskCreatorOptionsResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('计划任务创建人下拉选项')]
    #[Route(method: 'GET', path: '/tasks/creators')]
    public function listTaskCreators(): TaskCreatorOptionsResponse;

    /**
     * 创建定时任务
     * @param CronTaskCreateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronTaskRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('创建定时任务')]
    #[Route(method: 'POST', path: '/tasks')]
    public function createTask(CronTaskCreateRequest $request): CronTaskRowResponse;

    /**
     * 更新定时任务
     * @param CronTaskUpdateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronTaskRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('更新定时任务')]
    #[Route(method: 'PUT', path: '/tasks')]
    public function updateTask(CronTaskUpdateRequest $request): CronTaskRowResponse;

    /**
     * 删除定时任务
     * @param CronTaskIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronDeleteAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('删除定时任务')]
    #[Route(method: 'DELETE', path: '/tasks')]
    public function deleteTask(CronTaskIdRequest $request): CronDeleteAckResponse;

    /**
     * 切换定时任务启用状态
     * @param CronTaskStatusSwitchRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronTaskStatusAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('切换定时任务启用状态')]
    #[Route(method: 'POST', path: '/tasks/status')]
    public function switchTaskStatus(CronTaskStatusSwitchRequest $request): CronTaskStatusAckResponse;

    /**
     * 查询 Cron Agent 节点列表
     * @return CronNodeListResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('查询 Cron Agent 节点列表')]
    #[Route(method: 'GET', path: '/nodes')]
    public function listNodes(): CronNodeListResponse;

    /**
     * 创建 Cron Agent 节点
     * @param CronNodeCreateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronNodeCreateResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('创建 Cron Agent 节点')]
    #[Route(method: 'POST', path: '/nodes')]
    public function createNode(CronNodeCreateRequest $request): CronNodeCreateResponse;

    /**
     * 删除 Cron Agent 节点
     * @param CronNodeIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronDeleteAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('删除 Cron Agent 节点')]
    #[Route(method: 'DELETE', path: '/nodes')]
    public function deleteNode(CronNodeIdRequest $request): CronDeleteAckResponse;

    /**
     * 分页查询任务执行日志
     * @param TaskLogsQueryRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return TaskLogsResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('分页查询任务执行日志')]
    #[Route(method: 'GET', path: '/tasks/logs')]
    public function taskLogs(TaskLogsQueryRequest $request): TaskLogsResponse;

    /**
     * 执行记录趋势折线
     * @param TaskLogsTrendRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ExecutionTrendResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('执行记录趋势折线')]
    #[Route(method: 'GET', path: '/tasks/logs/trend')]
    public function taskLogsTrend(TaskLogsTrendRequest $request): ExecutionTrendResponse;

    /**
     * 分页查询计划任务操作记录
     * @param TaskOperationLogsQueryRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return TaskOperationLogsResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('分页查询计划任务操作记录')]
    #[Route(method: 'GET', path: '/tasks/operation-logs')]
    public function taskOperationLogs(TaskOperationLogsQueryRequest $request): TaskOperationLogsResponse;

    /**
     * 计划任务操作记录操作人选项
     * @return TaskOperationOperatorOptionsResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('计划任务操作记录操作人选项')]
    #[Route(method: 'GET', path: '/tasks/operation-logs/operators')]
    public function listTaskOperationOperators(): TaskOperationOperatorOptionsResponse;

    /**
     * 查询任务执行统计
     * @param CronTaskStatsQueryRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronTaskStatsResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('查询任务执行统计')]
    #[Route(method: 'GET', path: '/tasks/stats')]
    public function taskStats(CronTaskStatsQueryRequest $request): CronTaskStatsResponse;

    /**
     * Agent 拉取待执行的定时任务
     * @param CronAgentTasksQueryRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronAgentTasksResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('Agent 拉取待执行的定时任务')]
    #[Route(method: 'GET', path: '/agent/tasks')]
    public function agentTasks(CronAgentTasksQueryRequest $request): CronAgentTasksResponse;

    /**
     * Agent 心跳上报
     * @param CronAgentHeartbeatRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronAgentHeartbeatResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('Agent 心跳上报')]
    #[Route(method: 'POST', path: '/agent/heartbeat')]
    public function agentHeartbeat(CronAgentHeartbeatRequest $request): CronAgentHeartbeatResponse;

    /**
     * Agent 上报任务执行结果
     * @param CronAgentReportRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronAgentReportAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('Agent 上报任务执行结果')]
    #[Route(method: 'POST', path: '/agent/report')]
    public function agentReport(CronAgentReportRequest $request): CronAgentReportAckResponse;

    /**
     * 查询定时任务详情
     * @param CronTaskIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronTaskRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('查询定时任务详情')]
    #[Route(method: 'GET', path: '/tasks/detail')]
    public function getTask(CronTaskIdRequest $request): CronTaskRowResponse;

    /**
     * 预览 Cron 表达式
     * @param ExpressionPreviewRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ExpressionPreviewResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('预览 Cron 表达式')]
    #[Route(method: 'POST', path: '/tasks/expression/preview')]
    public function previewExpression(ExpressionPreviewRequest $request): ExpressionPreviewResponse;

    /**
     * 查询单次执行详情
     * @param ExecutionDetailRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ExecutionDetailResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('查询单次执行详情')]
    #[Route(method: 'GET', path: '/tasks/execution')]
    public function getExecution(ExecutionDetailRequest $request): ExecutionDetailResponse;

    /**
     * 取消一次执行
     * @param ExecutionCancelRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ExecutionCancelResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('取消一次执行')]
    #[Route(method: 'POST', path: '/executions/cancel')]
    public function cancelExecution(ExecutionCancelRequest $request): ExecutionCancelResponse;

    /**
     * Dashboard 概览
     * @return DashboardOverviewResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('Dashboard 概览')]
    #[Route(method: 'GET', path: '/dashboard/overview')]
    public function dashboardOverview(): DashboardOverviewResponse;

    /**
     * 执行趋势
     * @param DashboardTrendRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return ExecutionTrendResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('执行趋势')]
    #[Route(method: 'GET', path: '/dashboard/execution-trend')]
    public function executionTrend(DashboardTrendRequest $request): ExecutionTrendResponse;

    /**
     * Runtime 概览
     * @return RuntimeOverviewResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('Runtime 概览')]
    #[Route(method: 'GET', path: '/runtime/overview')]
    public function runtimeOverview(): RuntimeOverviewResponse;

    /**
     * 查询节点详情
     * @param CronNodeIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronNodeRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('查询节点详情')]
    #[Route(method: 'GET', path: '/nodes/detail')]
    public function getNode(CronNodeIdRequest $request): CronNodeRowResponse;

    /**
     * 更新节点
     * @param CronNodeUpdateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronNodeRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('更新节点')]
    #[Route(method: 'PUT', path: '/nodes')]
    public function updateNode(CronNodeUpdateRequest $request): CronNodeRowResponse;

    /**
     * 查询节点分组列表
     * @return CronNodeGroupListResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('查询节点分组列表')]
    #[Route(method: 'GET', path: '/node-groups')]
    public function listNodeGroups(): CronNodeGroupListResponse;

    /**
     * 创建节点分组
     * @param CronNodeGroupCreateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronNodeGroupRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('创建节点分组')]
    #[Route(method: 'POST', path: '/node-groups')]
    public function createNodeGroup(CronNodeGroupCreateRequest $request): CronNodeGroupRowResponse;

    /**
     * 更新节点分组
     * @param CronNodeGroupUpdateRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronNodeGroupRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('更新节点分组')]
    #[Route(method: 'PUT', path: '/node-groups')]
    public function updateNodeGroup(CronNodeGroupUpdateRequest $request): CronNodeGroupRowResponse;

    /**
     * 查询节点分组详情
     * @param CronNodeGroupIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronNodeGroupRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('查询节点分组详情')]
    #[Route(method: 'GET', path: '/node-groups/detail')]
    public function getNodeGroup(CronNodeGroupIdRequest $request): CronNodeGroupRowResponse;

    /**
     * 删除节点分组
     * @param CronNodeGroupIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronDeleteAckResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('删除节点分组')]
    #[Route(method: 'DELETE', path: '/node-groups')]
    public function deleteNodeGroup(CronNodeGroupIdRequest $request): CronDeleteAckResponse;

    /**
     * 批量启停任务
     * @param BatchStatusRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return BatchStatusResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('批量启停任务')]
    #[Route(method: 'PUT', path: '/tasks/batch-status')]
    public function batchSwitchStatus(BatchStatusRequest $request): BatchStatusResponse;

    /**
     * 复制定时任务
     * @param CronTaskIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronTaskRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('复制定时任务')]
    #[Route(method: 'POST', path: '/tasks/duplicate')]
    public function duplicateTask(CronTaskIdRequest $request): CronTaskRowResponse;

    /**
     * 入队手动执行一次
     * @param CronTaskIdRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return RunOnceQueuedResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('入队手动执行一次')]
    #[Route(method: 'POST', path: '/tasks/run')]
    public function runTaskOnce(CronTaskIdRequest $request): RunOnceQueuedResponse;

    /**
     * 变更任务权限所属人
     * @param TaskTransferOwnerRequest $request 请求参数（字段见 Request DTO 上 ApiProperty）
     * @return CronTaskRowResponse 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）
     */
    #[ApiOperation('变更任务权限所属人')]
    #[Route(method: 'POST', path: '/tasks/owner')]
    public function transferTaskOwner(TaskTransferOwnerRequest $request): CronTaskRowResponse;
}
