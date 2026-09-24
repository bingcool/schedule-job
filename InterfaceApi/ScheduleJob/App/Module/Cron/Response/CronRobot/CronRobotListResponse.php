<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot;

use InterfaceApi\ScheduleJob\App\Module\Common\Http\BaseListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;

class CronRobotListResponse extends BaseListResponse
{
    #[ApiProperty(description: '机器人列表 data')]
    protected CronRobotListDataDto $data;

    /**
     * @param CronRobotListDataDto|list<\InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotRowDto|array<string, mixed>> $list
     */
    public function __construct(CronRobotListDataDto|array $list)
    {
        $this->data = $list instanceof CronRobotListDataDto
            ? $list
            : CronRobotListDataDto::fromItems($list);
    }

    public function getData(): CronRobotListDataDto
    {
        return $this->data;
    }

    /**
     * @param CronRobotListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronRobotListDataDto) {
            throw new InvalidArgumentException('data must be CronRobotListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
