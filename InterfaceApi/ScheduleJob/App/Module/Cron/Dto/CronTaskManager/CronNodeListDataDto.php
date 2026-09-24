<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ArrayList;

class CronNodeListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, CronAgentNodeRowDto>
     */
    #[ApiProperty(description: '节点列表')]
    #[ArrayList(itemClass: CronAgentNodeRowDto::class)]
    protected array $list = [];

    /**
     * @return array<int, CronAgentNodeRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, CronAgentNodeRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof CronAgentNodeRowDto)) {
            throw new InvalidArgumentException('list items must be instances of CronAgentNodeRowDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(CronAgentNodeRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<CronAgentNodeRowDto> $items
     */
    public static function fromItems(array $items): self
    {
        $dto = new self();
        foreach ($items as $item) {
            if (!$item instanceof CronAgentNodeRowDto) {
                throw new InvalidArgumentException('list items must be instances of CronAgentNodeRowDto');
            }
            $dto->addListItem($item);
        }
        $dto->setTotal(count($dto->getList()));

        return $dto;
    }
}
