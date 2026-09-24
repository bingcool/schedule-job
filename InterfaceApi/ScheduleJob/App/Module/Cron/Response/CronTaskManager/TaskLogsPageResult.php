<?php

declare(strict_types=1);


namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskLogRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ArrayList;
use InterfaceApi\Support\ArrayDto;

class TaskLogsPageResult extends ArrayDto
{
    protected int $total = 0;

    /**
     * @var array<int, CronTaskLogRowDto>
     */
    #[ArrayList(
        itemClass: CronTaskLogRowDto::class
    )]
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
     * @return array<int, CronTaskLogRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, CronTaskLogRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof CronTaskLogRowDto)) {
            throw new InvalidArgumentException('list items must be instances of CronTaskLogRowDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(CronTaskLogRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }
}
