<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * Cron 任务执行日志行 DTO。
 *
 * **职责**：表示 cron_task_log 表的单条执行记录，字段与数据库查询结果对齐（camelCase 输出）。
 *
 * **生产者**：{@see \InterfaceApi\ScheduleJob\App\Module\Cron\Service\CronTaskManagerService::taskLogs} 通过
 * {@see static::fromEntityRow} 将实体行映射为 DTO；Agent 上报由 Service 直接写库。
 *
 * **消费者**：{@see \InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\TaskLogsPageResult} 收集列表项；
 * API 序列化时通过 ApiProperty 注解生成文档。
 *
 * **关键字段语义**：
 * - cronId：关联的 cron_task 主键
 * - execType：1=shell，2=http，3=kubernetes（来自关联任务，任务已删时回落 taskItem）
 * - execBatchId：同一次调度触发的批次号
 * - taskItem：执行时的任务元数据快照（JSON 列）
 * - message：人类可读运行信息，禁止用于统计
 * - status / statusName：结构化执行状态
 */
class CronTaskLogRowDto extends AbstractDto
{
    #[ApiProperty(description: '日志 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '关联任务 ID')]
    protected int $cronId = 0;

    #[ApiProperty(description: '任务名称')]
    protected string $taskName = '';

    #[ApiProperty(description: '执行批次 ID')]
    protected string $execBatchId = '';

    #[ApiProperty(description: '执行进程 PID')]
    protected int $pid = 0;

    #[ApiProperty(description: '执行状态整型：0-register 1-running 2-success 3-failed 4-skipped 5-timeout 6-cancelled 7-unregister')]
    protected int $status = 0;

    #[ApiProperty(description: '执行状态名称')]
    protected string $statusName = 'register';

    #[ApiProperty(description: '触发类型：1-scheduler 2-run_once')]
    protected int $triggerType = 0;

    #[ApiProperty(description: '执行类型：1=shell, 2=http, 3=kubernetes')]
    protected int $execType = 0;

    #[ApiProperty(description: '注册/解除当时的任务状态：1=启用，0=禁用；无法判定时为 null')]
    protected ?int $taskStatus = null;

    #[ApiProperty(description: '手动执行请求 ID')]
    protected ?int $requestId = null;

    #[ApiProperty(description: '执行节点 ID')]
    protected int $nodeId = 0;

    #[ApiProperty(description: 'Lease owner')]
    protected string $leaseOwner = '';

    #[ApiProperty(description: 'Lease 过期时间')]
    protected string $leaseUntil = '';

    #[ApiProperty(description: '心跳时间')]
    protected string $heartbeatAt = '';

    #[ApiProperty(description: '超时截止时间')]
    protected string $timeoutAt = '';

    #[ApiProperty(description: '取消请求时间')]
    protected string $cancelledAt = '';

    #[ApiProperty(description: '失败原因')]
    protected string $failureReason = '';

    #[ApiProperty(description: '计划执行时间')]
    protected string $scheduledAt = '';

    #[ApiProperty(description: '实际开始时间')]
    protected string $startedAt = '';

    #[ApiProperty(description: '实际结束时间')]
    protected string $finishedAt = '';

    #[ApiProperty(description: '执行耗时毫秒')]
    protected int $durationMs = 0;

    #[ApiProperty(description: 'Shell 退出码')]
    protected ?int $exitCode = null;

    #[ApiProperty(description: 'HTTP 状态码')]
    protected ?int $httpStatus = null;

    /**
     * 任务项快照，执行时的调度元数据。
     *
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: '任务项快照')]
    protected ?array $taskItem = null;

    #[ApiProperty(description: '执行流水（按时间追加的关键步骤）')]
    protected string $message = '';

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    #[ApiProperty(description: '更新时间')]
    protected string $updatedAt = '';

    

    

    

    

    

    /** 获取日志 ID */
    public function getId(): int
    {
        return $this->id;
    }

    /** 设置日志 ID */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /** 获取关联任务 ID */
    public function getCronId(): int
    {
        return $this->cronId;
    }

    /** 设置关联任务 ID */
    public function setCronId(int $cronId): static
    {
        $this->cronId = $cronId;

        return $this;
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function setTaskName(string $taskName): static
    {
        $this->taskName = $taskName;

        return $this;
    }

    /** 获取执行批次 ID */
    public function getExecBatchId(): string
    {
        return $this->execBatchId;
    }

    /** 设置执行批次 ID */
    public function setExecBatchId(string $execBatchId): static
    {
        $this->execBatchId = $execBatchId;

        return $this;
    }

    /** 获取执行进程 PID */
    public function getPid(): int
    {
        return $this->pid;
    }

    /** 设置执行进程 PID */
    public function setPid(int $pid): static
    {
        $this->pid = $pid;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusName(): string
    {
        return $this->statusName;
    }

    public function setStatusName(string $statusName): static
    {
        $this->statusName = $statusName;

        return $this;
    }

    public function getTriggerType(): int
    {
        return $this->triggerType;
    }

    public function setTriggerType(int $triggerType): static
    {
        $this->triggerType = $triggerType;

        return $this;
    }

    public function getExecType(): int
    {
        return $this->execType;
    }

    public function setExecType(int $execType): static
    {
        $this->execType = $execType;

        return $this;
    }

    public function getTaskStatus(): ?int
    {
        return $this->taskStatus;
    }

    public function setTaskStatus(?int $taskStatus): static
    {
        $this->taskStatus = $taskStatus;

        return $this;
    }

    public function getScheduledAt(): string
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(string $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getStartedAt(): string
    {
        return $this->startedAt;
    }

    public function setStartedAt(string $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): string
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(string $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getDurationMs(): int
    {
        return $this->durationMs;
    }

    public function setDurationMs(int $durationMs): static
    {
        $this->durationMs = $durationMs;

        return $this;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function setExitCode(?int $exitCode): static
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function setHttpStatus(?int $httpStatus): static
    {
        $this->httpStatus = $httpStatus;

        return $this;
    }

    /**
     * 获取任务项快照。
     *
     * @return array<string, mixed>|null
     */
    public function getTaskItem(): ?array
    {
        return $this->taskItem;
    }

    /**
     * 设置任务项快照。
     *
     * @param array<string, mixed>|null $taskItem
     */
    public function setTaskItem(?array $taskItem): static
    {
        $this->taskItem = $taskItem;

        return $this;
    }

    /** 获取运行消息 */
    public function getMessage(): string
    {
        return $this->message;
    }

    /** 设置运行消息 */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /** 获取创建时间 */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /** 设置创建时间 */
    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** 获取更新时间 */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /** 设置更新时间 */
    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
