<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class AgentReportAckDto extends AbstractDto
{
    #[ApiProperty(description: '是否已保存')]
    protected bool $saved = true;

    #[ApiProperty(description: 'Cron 任务 ID')]
    protected int $cronId = 0;

    public static function of(int $cronId, bool $saved = true): self
    {
        $dto = new self();
        $dto->cronId = $cronId;
        $dto->saved = $saved;

        return $dto;
    }
}
