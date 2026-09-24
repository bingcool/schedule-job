<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

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
