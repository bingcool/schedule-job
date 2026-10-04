<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 批量启停结果。
 */
class BatchStatusResultDto extends AbstractDto
{
    /**
     * @var list<int>
     */
    #[ApiProperty(description: '已更新的任务 ID')]
    protected array $updatedIds = [];

    #[ApiProperty(description: '目标状态')]
    protected int $status = 0;

    /**
     * @param list<int> $updatedIds
     */
    public static function of(array $updatedIds, int $status): self
    {
        $dto = new self();
        $dto->updatedIds = $updatedIds;
        $dto->status = $status;

        return $dto;
    }

    public function getUpdatedIds(): array
    {
        return $this->updatedIds;
    }

    public function setUpdatedIds(array $updatedIds): static
    {
        $this->updatedIds = $updatedIds;

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
}
