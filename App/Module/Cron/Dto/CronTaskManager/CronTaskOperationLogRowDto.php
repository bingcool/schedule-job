<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronTaskManager;

use App\Module\Cron\CronTaskOperationType;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

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

    /**
     * @param array<string, mixed> $row
     */
    public static function fromEntityRow(array $row): self
    {
        $dto = new self();
        $actionType = (int) ($row['action_type'] ?? $row['actionType'] ?? 0);
        $dto->setId((int) ($row['id'] ?? 0));
        $dto->setCronId((int) ($row['cron_id'] ?? $row['cronId'] ?? 0));
        $dto->setTaskName((string) ($row['task_name'] ?? $row['taskName'] ?? ''));
        $dto->setActionType($actionType);
        $dto->setActionTypeName(CronTaskOperationType::label($actionType));
        $dto->setOperatorId((int) ($row['operator_id'] ?? $row['operatorId'] ?? 0));
        $dto->setOperatorName((string) ($row['operator_name'] ?? $row['operatorName'] ?? ''));
        $before = $row['content_before'] ?? $row['contentBefore'] ?? null;
        $after = $row['content_after'] ?? $row['contentAfter'] ?? null;
        $dto->setContentBefore(self::decodeJsonField($before));
        $dto->setContentAfter(self::decodeJsonField($after));
        $dto->setCreatedAt((string) ($row['created_at'] ?? $row['createdAt'] ?? ''));

        return $dto;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeJsonField(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
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
