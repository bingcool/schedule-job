<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ArrayList;

class TaskOperationOperatorOptionsListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, TaskOperationOperatorOptionDto>
     */
    #[ApiProperty(description: '操作人下拉选项')]
    #[ArrayList(itemClass: TaskOperationOperatorOptionDto::class)]
    protected array $list = [];

    /**
     * @return array<int, TaskOperationOperatorOptionDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, TaskOperationOperatorOptionDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof TaskOperationOperatorOptionDto)) {
            throw new InvalidArgumentException('list items must be instances of TaskOperationOperatorOptionDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(TaskOperationOperatorOptionDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<TaskOperationOperatorOptionDto> $items
     */
    public static function fromItems(array $items): self
    {
        $dto = new self();
        foreach ($items as $item) {
            $dto->addListItem($item);
        }
        $dto->setTotal(count($dto->getList()));

        return $dto;
    }
}
