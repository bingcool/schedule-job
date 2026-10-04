<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 趋势时间桶。
 */
class ExecutionTrendBucketDto extends AbstractDto
{
    #[ApiProperty(description: '桶标签，如 02:00 或 2026-08-17')]
    protected string $time = '';

    #[ApiProperty(description: '总次数')]
    protected int $total = 0;

    #[ApiProperty(description: '成功次数')]
    protected int $success = 0;

    #[ApiProperty(description: '失败次数')]
    protected int $failed = 0;

    #[ApiProperty(description: '超时次数')]
    protected int $timeout = 0;

    #[ApiProperty(description: '跳过次数')]
    protected int $skipped = 0;

    #[ApiProperty(description: '取消次数')]
    protected int $cancelled = 0;

    public static function of(
        string $time,
        int $total,
        int $success,
        int $failed,
        int $timeout = 0,
        int $skipped = 0,
        int $cancelled = 0,
    ): self {
        $dto = new self();
        $dto->time = $time;
        $dto->total = $total;
        $dto->success = $success;
        $dto->failed = $failed;
        $dto->timeout = $timeout;
        $dto->skipped = $skipped;
        $dto->cancelled = $cancelled;

        return $dto;
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function setTime(string $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function setSuccess(int $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function setFailed(int $failed): static
    {
        $this->failed = $failed;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function setSkipped(int $skipped): static
    {
        $this->skipped = $skipped;

        return $this;
    }

    public function getCancelled(): int
    {
        return $this->cancelled;
    }

    public function setCancelled(int $cancelled): static
    {
        $this->cancelled = $cancelled;

        return $this;
    }
}
