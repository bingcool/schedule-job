<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot;

use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\ScheduleJob\App\Module\Common\Http\BasePageResultResponse;

class CronRobotAlertLogsResponse extends BasePageResultResponse
{
    #[ApiProperty(description: '投递记录分页 data')]
    protected CronRobotAlertLogsPageResult $data;

    public function __construct(CronRobotAlertLogsPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): CronRobotAlertLogsPageResult
    {
        return $this->data;
    }

    /**
     * @param CronRobotAlertLogsPageResult $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronRobotAlertLogsPageResult) {
            throw new InvalidArgumentException('data must be CronRobotAlertLogsPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
