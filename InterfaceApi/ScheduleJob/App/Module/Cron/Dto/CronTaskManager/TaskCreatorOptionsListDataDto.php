<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ArrayList;

class TaskCreatorOptionsListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, TaskCreatorOptionDto>
     */
    #[ApiProperty(description: '创建人下拉选项')]
    #[ArrayList(itemClass: TaskCreatorOptionDto::class)]
    protected array $list = [];

    /**
     * @return array<int, TaskCreatorOptionDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, TaskCreatorOptionDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof TaskCreatorOptionDto)) {
            throw new InvalidArgumentException('list items must be instances of TaskCreatorOptionDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(TaskCreatorOptionDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<TaskCreatorOptionDto> $items
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
