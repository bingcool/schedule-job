<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskOperationLogRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ArrayList;
use InterfaceApi\Support\ArrayDto;

class TaskOperationLogsPageResult extends ArrayDto
{
    protected int $total = 0;

    /**
     * @var array<int, CronTaskOperationLogRowDto>
     */
    #[ArrayList(
        itemClass: CronTaskOperationLogRowDto::class
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
     * @return array<int, CronTaskOperationLogRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    public function addListItem(CronTaskOperationLogRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param array<int, CronTaskOperationLogRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof CronTaskOperationLogRowDto)) {
            throw new InvalidArgumentException('list items must be instances of CronTaskOperationLogRowDto');
        }
        $this->list = $list;

        return $this;
    }
}
