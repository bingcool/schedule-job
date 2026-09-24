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
}
