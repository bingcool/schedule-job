<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

/**
 * 执行记录折线查询。与列表共用过滤字段，但不要求 page / pageSize。
 */
class TaskLogsTrendRequest extends BaseRequest
{
    #[ApiProperty(description: '任务主键ID，不传则返回全部任务日志')]
    #[ValidationRule(rule: 'nullable|int', message: 'taskId 必须是整数')]
    #[StringToInt]
    protected ?int $taskId = null;

    #[ApiProperty(description: '执行批次 ID')]
    protected ?string $execBatchId = null;

    #[ApiProperty(description: '执行类型：1=shell, 2=http, 3=kubernetes')]
    #[ValidationRule(rule: 'nullable|int', message: 'execType 必须是整数')]
    #[StringToInt]
    protected ?int $execType = null;

    #[ApiProperty(description: '触发类型：1=定时, 2=手动执行')]
    #[ValidationRule(rule: 'nullable|int', message: 'triggerType 必须是整数')]
    #[StringToInt]
    protected ?int $triggerType = null;

    #[ApiProperty(description: '任务名称（模糊搜索）')]
    protected ?string $taskName = null;

    #[ApiProperty(description: '开始时间（含）')]
    protected ?string $startTime = null;

    #[ApiProperty(description: '结束时间（含）')]
    protected ?string $endTime = null;

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(?int $taskId): static
    {
        $this->taskId = $taskId;

        return $this;
    }

    public function getExecBatchId(): ?string
    {
        return $this->execBatchId;
    }

    public function setExecBatchId(?string $execBatchId): static
    {
        $this->execBatchId = $execBatchId;

        return $this;
    }

    public function getExecType(): ?int
    {
        return $this->execType;
    }

    public function setExecType(?int $execType): static
    {
        $this->execType = $execType;

        return $this;
    }

    public function getTriggerType(): ?int
    {
        return $this->triggerType;
    }

    public function setTriggerType(?int $triggerType): static
    {
        $this->triggerType = $triggerType;

        return $this;
    }

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function setTaskName(?string $taskName): static
    {
        $this->taskName = $taskName;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime !== null && trim($this->startTime) !== '' ? trim($this->startTime) : null;
    }

    public function setStartTime(?string $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime !== null && trim($this->endTime) !== '' ? trim($this->endTime) : null;
    }

    public function setEndTime(?string $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }
}
