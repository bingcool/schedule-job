<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ArrayList;

class CronNodeGroupListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, CronAgentNodeGroupRowDto>
     */
    #[ApiProperty(description: '节点分组列表')]
    #[ArrayList(itemClass: CronAgentNodeGroupRowDto::class)]
    protected array $list = [];

    /**
     * @return array<int, CronAgentNodeGroupRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, CronAgentNodeGroupRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof CronAgentNodeGroupRowDto)) {
            throw new InvalidArgumentException('list items must be instances of CronAgentNodeGroupRowDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(CronAgentNodeGroupRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<CronAgentNodeGroupRowDto|array<string, mixed>> $items
     */
    public static function fromItems(array $items): self
    {
        $dto = new self();
        foreach ($items as $item) {
            $dto->addListItem($item instanceof CronAgentNodeGroupRowDto
                ? $item
                : CronAgentNodeGroupRowDto::fromEntityRow($item));
        }
        $dto->setTotal(count($dto->getList()));

        return $dto;
    }
}
