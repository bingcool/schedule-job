<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class TaskOperationLogsQueryDto extends AbstractDto
{
    #[ApiProperty(description: '当前页码，从 1 开始')]
    protected int $page = 1;

    #[ApiProperty(description: '每页条数')]
    protected int $pageSize = 20;

    #[ApiProperty(description: '任务名称关键词（模糊匹配）')]
    protected ?string $taskName = null;

    #[ApiProperty(description: '操作类型：1=启用 2=禁用 3=删除 4=执行 5=编辑')]
    protected ?int $actionType = null;

    #[ApiProperty(description: '操作人 staff_user.id')]
    protected ?int $operatorId = null;

    #[ApiProperty(description: '操作开始时间（含）')]
    protected ?string $startTime = null;

    #[ApiProperty(description: '操作结束时间（含）')]
    protected ?string $endTime = null;

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): static
    {
        $this->page = max(1, $page);

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): static
    {
        $this->pageSize = max(1, $pageSize);

        return $this;
    }

    public function getOffset(): int
    {
        return ($this->getPage() - 1) * $this->getPageSize();
    }

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function setTaskName(?string $taskName): static
    {
        $this->taskName = $taskName !== null && trim($taskName) !== '' ? trim($taskName) : null;

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
        $this->startTime = $startTime !== null && trim($startTime) !== '' ? trim($startTime) : null;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(?string $endTime): static
    {
        $this->endTime = $endTime !== null && trim($endTime) !== '' ? trim($endTime) : null;

        return $this;
    }
}
