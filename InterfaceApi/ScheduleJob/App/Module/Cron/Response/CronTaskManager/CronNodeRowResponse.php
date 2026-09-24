<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronNodeRowResponse extends BaseResponse
{
    #[ApiProperty(description: '节点详情 data')]
    protected CronAgentNodeRowDto $data;

    public function __construct(CronAgentNodeRowDto $data)
    {
        $this->data = $data;
    }

    public function getData(): CronAgentNodeRowDto
    {
        return $this->data;
    }

    /**
     * @param CronAgentNodeRowDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronAgentNodeRowDto) {
            throw new InvalidArgumentException('data must be CronAgentNodeRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
