<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class ExecutionCancelResultDto extends AbstractDto
{
    #[ApiProperty(description: '执行日志 ID')]
    protected int $executionId = 0;

    #[ApiProperty(description: '当前状态名')]
    protected string $status = '';

    #[ApiProperty(description: '是否已结束（Cancel 未被接受）')]
    protected bool $alreadyFinished = false;

    public static function accepted(int $executionId, string $status): self
    {
        $dto = new self();
        $dto->executionId = $executionId;
        $dto->status = $status;
        $dto->alreadyFinished = false;

        return $dto;
    }

    public static function alreadyFinished(int $executionId, string $status): self
    {
        $dto = new self();
        $dto->executionId = $executionId;
        $dto->status = $status;
        $dto->alreadyFinished = true;

        return $dto;
    }

    public function getExecutionId(): int
    {
        return $this->executionId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isAlreadyFinished(): bool
    {
        return $this->alreadyFinished;
    }
}
