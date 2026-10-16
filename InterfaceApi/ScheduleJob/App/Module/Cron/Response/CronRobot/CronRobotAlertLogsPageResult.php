<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotAlertLogRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ArrayList;
use InterfaceApi\Support\ArrayDto;

class CronRobotAlertLogsPageResult extends ArrayDto
{
    protected int $total = 0;

    /**
     * @var array<int, CronRobotAlertLogRowDto>
     */
    #[ArrayList(itemClass: CronRobotAlertLogRowDto::class)]
    protected array $list = [];

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array<int, CronRobotAlertLogRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, CronRobotAlertLogRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof CronRobotAlertLogRowDto)) {
            throw new InvalidArgumentException('list items must be instances of CronRobotAlertLogRowDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(CronRobotAlertLogRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }
}
