<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 手动执行入队回执。不声称已执行，只表示等待 Cron Worker 执行 runOnceNow。
 */
class RunOnceQueuedDto extends AbstractDto
{
    #[ApiProperty(description: '任务 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '是否已入队')]
    protected bool $queued = true;

    #[ApiProperty(description: '入队时间')]
    protected string $requestedAt = '';

    #[ApiProperty(description: '说明')]
    protected string $message = '';

    public static function of(int $id, string $requestedAt): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->queued = true;
        $dto->requestedAt = $requestedAt;
        $dto->message = '已入队，等待 Cron Worker 执行 runOnceNow';

        return $dto;
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

    public function getQueued(): bool
    {
        return $this->queued;
    }

    public function setQueued(bool $queued): static
    {
        $this->queued = $queued;

        return $this;
    }

    public function getRequestedAt(): string
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(string $requestedAt): static
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }
}
