<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskStatsResultDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronTaskStatsResponse extends BaseResponse
{
    #[ApiProperty(description: '任务执行统计 data')]
    protected CronTaskStatsResultDto $data;

    public function __construct(CronTaskStatsResultDto $stats)
    {
        $this->data = $stats;
    }

    public static function fromDto(CronTaskStatsResultDto $stats): self
    {
        return new self($stats);
    }

    public function getData(): CronTaskStatsResultDto
    {
        return $this->data;
    }

    /**
     * @param CronTaskStatsResultDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronTaskStatsResultDto) {
            throw new InvalidArgumentException('data must be CronTaskStatsResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
