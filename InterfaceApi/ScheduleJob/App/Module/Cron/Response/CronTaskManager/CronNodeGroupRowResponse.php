<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeGroupRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronNodeGroupRowResponse extends BaseResponse
{
    #[ApiProperty(description: '节点分组详情 data')]
    protected CronAgentNodeGroupRowDto $data;

    public function __construct(CronAgentNodeGroupRowDto $data)
    {
        $this->data = $data;
    }

    public function getData(): CronAgentNodeGroupRowDto
    {
        return $this->data;
    }

    /**
     * @param CronAgentNodeGroupRowDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronAgentNodeGroupRowDto) {
            throw new InvalidArgumentException('data must be CronAgentNodeGroupRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
