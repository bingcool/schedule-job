<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronRobotRowResponse extends BaseResponse
{
    #[ApiProperty(description: '机器人详情 data')]
    protected CronRobotRowDto $data;

    public function __construct(CronRobotRowDto $data)
    {
        $this->data = $data;
    }

    public function getData(): CronRobotRowDto
    {
        return $this->data;
    }

    /**
     * @param CronRobotRowDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronRobotRowDto) {
            throw new InvalidArgumentException('data must be CronRobotRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
