<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 单次 Execution 查询：cron_id + exec_batch_id。
 */
class ExecutionDetailQueryDto extends AbstractDto
{
    #[ApiProperty(description: '日志行 ID（优先命中）')]
    protected ?int $logId = null;

    #[ApiProperty(description: '任务 ID')]
    protected int $taskId = 0;

    #[ApiProperty(description: '执行批次 ID')]
    protected string $execBatchId = '';

    public static function of(int $taskId, string $execBatchId, ?int $logId = null): self
    {
        $dto = new self();
        $dto->logId = $logId !== null && $logId > 0 ? $logId : null;
        $dto->taskId = $taskId;
        $dto->execBatchId = trim($execBatchId);

        return $dto;
    }

    public function getLogId(): ?int
    {
        return $this->logId;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getExecBatchId(): string
    {
        return $this->execBatchId;
    }
}
