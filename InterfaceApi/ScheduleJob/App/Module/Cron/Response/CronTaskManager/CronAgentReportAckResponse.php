<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\AgentReportAckDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronAgentReportAckResponse extends BaseResponse
{
    #[ApiProperty(description: 'Agent 上报确认 data')]
    protected AgentReportAckDto $data;

    public function __construct(int $cronId, bool $saved = true)
    {
        $this->data = AgentReportAckDto::of($cronId, $saved);
    }

    public function getData(): AgentReportAckDto
    {
        return $this->data;
    }

    /**
     * @param AgentReportAckDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof AgentReportAckDto) {
            throw new InvalidArgumentException('data must be AgentReportAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
