<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 单次执行详情（由 cron_task_log 结构化字段读取，不解析 message 推断状态）。
 */
class ExecutionDetailDto extends AbstractDto
{
    #[ApiProperty(description: '执行日志 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '任务 ID')]
    protected int $taskId = 0;

    #[ApiProperty(description: '执行批次 ID')]
    protected string $execBatchId = '';

    #[ApiProperty(description: 'register / running / success / failed / skipped / timeout / cancelled / cancel_requested / unregister / unknown')]
    protected string $status = 'unknown';

    #[ApiProperty(description: 'status 整型')]
    protected int $statusCode = 0;

    #[ApiProperty(description: '触发类型：1-scheduler 2-run_once')]
    protected int $triggerType = 0;

    #[ApiProperty(description: '执行类型：1=shell, 2=http, 3=kubernetes')]
    protected int $execType = 0;

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

    #[ApiProperty(description: '进程 PID')]
    protected int $pid = 0;

    #[ApiProperty(description: '计划执行时间')]
    protected string $scheduledAt = '';

    #[ApiProperty(description: '开始时间')]
    protected string $startedAt = '';

    #[ApiProperty(description: '结束时间')]
    protected string $finishedAt = '';

    #[ApiProperty(description: '耗时毫秒')]
    protected float $durationMs = 0.0;

    #[ApiProperty(description: 'Shell 退出码')]
    protected ?int $exitCode = null;

    #[ApiProperty(description: 'HTTP 状态码')]
    protected ?int $httpStatus = null;

    /**
     * @var array<string, mixed>
     */
    #[ApiProperty(description: '执行时任务快照')]
    protected array $taskItem = [];

    #[ApiProperty(description: '执行时快照中的 Command / URL')]
    protected string $command = '';

    #[ApiProperty(description: '任务名称')]
    protected string $taskName = '';

    #[ApiProperty(description: '执行流水（开始执行 / PID / 重试 / 终态等，按时间追加）')]
    protected string $message = '';

    

    

    

    

    

    

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getExecBatchId(): string
    {
        return $this->execBatchId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getTriggerType(): int
    {
        return $this->triggerType;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getStartedAt(): string
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): string
    {
        return $this->finishedAt;
    }

    public function getDurationMs(): float
    {
        return $this->durationMs;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setTaskId(int $taskId): static
    {
        $this->taskId = $taskId;

        return $this;
    }

    public function setExecBatchId(string $execBatchId): static
    {
        $this->execBatchId = $execBatchId;

        return $this;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
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

    public function getRequestId(): ?int
    {
        return $this->requestId;
    }

    public function setRequestId(?int $requestId): static
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    public function setNodeId(int $nodeId): static
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    public function getLeaseOwner(): string
    {
        return $this->leaseOwner;
    }

    public function setLeaseOwner(string $leaseOwner): static
    {
        $this->leaseOwner = $leaseOwner;

        return $this;
    }

    public function getLeaseUntil(): string
    {
        return $this->leaseUntil;
    }

    public function setLeaseUntil(string $leaseUntil): static
    {
        $this->leaseUntil = $leaseUntil;

        return $this;
    }

    public function getHeartbeatAt(): string
    {
        return $this->heartbeatAt;
    }

    public function setHeartbeatAt(string $heartbeatAt): static
    {
        $this->heartbeatAt = $heartbeatAt;

        return $this;
    }

    public function getTimeoutAt(): string
    {
        return $this->timeoutAt;
    }

    public function setTimeoutAt(string $timeoutAt): static
    {
        $this->timeoutAt = $timeoutAt;

        return $this;
    }

    public function getCancelledAt(): string
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(string $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    public function setFailureReason(string $failureReason): static
    {
        $this->failureReason = $failureReason;

        return $this;
    }

    public function setPid(int $pid): static
    {
        $this->pid = $pid;

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

    public function setStartedAt(string $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function setFinishedAt(string $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function setDurationMs(float $durationMs): static
    {
        $this->durationMs = $durationMs;

        return $this;
    }

    public function setExitCode(?int $exitCode): static
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    public function setHttpStatus(?int $httpStatus): static
    {
        $this->httpStatus = $httpStatus;

        return $this;
    }

    public function getTaskItem(): array
    {
        return $this->taskItem;
    }

    public function setTaskItem(array $taskItem): static
    {
        $this->taskItem = $taskItem;

        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): static
    {
        $this->command = $command;

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

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }
}
