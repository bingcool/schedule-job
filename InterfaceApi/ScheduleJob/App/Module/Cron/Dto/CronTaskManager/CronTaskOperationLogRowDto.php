<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class CronTaskOperationLogRowDto extends AbstractDto
{
    #[ApiProperty(description: '日志 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '任务 ID')]
    protected int $cronId = 0;

    #[ApiProperty(description: '任务名称快照')]
    protected string $taskName = '';

    #[ApiProperty(description: '操作类型')]
    protected int $actionType = 0;

    #[ApiProperty(description: '操作类型名称')]
    protected string $actionTypeName = '';

    #[ApiProperty(description: '操作人 ID')]
    protected int $operatorId = 0;

    #[ApiProperty(description: '操作人展示名')]
    protected string $operatorName = '';

    /** @var array<string, mixed>|null */
    #[ApiProperty(description: '变更前任务内容')]
    protected ?array $contentBefore = null;

    /** @var array<string, mixed>|null */
    #[ApiProperty(description: '变更后任务内容')]
    protected ?array $contentAfter = null;

    #[ApiProperty(description: '操作时间')]
    protected string $createdAt = '';

    

    

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCronId(): int
    {
        return $this->cronId;
    }

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

    public function getActionType(): int
    {
        return $this->actionType;
    }

    public function setActionType(int $actionType): static
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getActionTypeName(): string
    {
        return $this->actionTypeName;
    }

    public function setActionTypeName(string $actionTypeName): static
    {
        $this->actionTypeName = $actionTypeName;

        return $this;
    }

    public function getOperatorId(): int
    {
        return $this->operatorId;
    }

    public function setOperatorId(int $operatorId): static
    {
        $this->operatorId = $operatorId;

        return $this;
    }

    public function getOperatorName(): string
    {
        return $this->operatorName;
    }

    public function setOperatorName(string $operatorName): static
    {
        $this->operatorName = $operatorName;

        return $this;
    }

    public function getContentBefore(): ?array
    {
        return $this->contentBefore;
    }

    public function setContentBefore(?array $contentBefore): static
    {
        $this->contentBefore = $contentBefore;

        return $this;
    }

    public function getContentAfter(): ?array
    {
        return $this->contentAfter;
    }

    public function setContentAfter(?array $contentAfter): static
    {
        $this->contentAfter = $contentAfter;

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
