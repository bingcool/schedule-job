<?php

declare(strict_types=1);

namespace App\Module\Cron\Request\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BasePageRequest;

class TaskOperationLogsQueryRequest extends BasePageRequest
{
    #[ApiProperty(description: '任务名称（模糊搜索）')]
    protected ?string $taskName = null;

    #[ApiProperty(description: '操作类型：1=启用 2=禁用 3=删除 4=执行 5=编辑')]
    #[ValidationRule(rule: 'nullable|int', message: 'actionType 必须是整数')]
    #[StringToInt]
    protected ?int $actionType = null;

    #[ApiProperty(description: '操作人 staff_user.id')]
    #[ValidationRule(rule: 'nullable|int', message: 'operatorId 必须是整数')]
    #[StringToInt]
    protected ?int $operatorId = null;

    #[ApiProperty(description: '操作开始时间（含）')]
    protected ?string $startTime = null;

    #[ApiProperty(description: '操作结束时间（含）')]
    protected ?string $endTime = null;

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function setTaskName(?string $taskName): static
    {
        $this->taskName = $taskName;

        return $this;
    }

    public function getActionType(): ?int
    {
        return $this->actionType;
    }

    public function setActionType(?int $actionType): static
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getOperatorId(): ?int
    {
        return $this->operatorId;
    }

    public function setOperatorId(?int $operatorId): static
    {
        $this->operatorId = $operatorId;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(?string $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(?string $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }
}
