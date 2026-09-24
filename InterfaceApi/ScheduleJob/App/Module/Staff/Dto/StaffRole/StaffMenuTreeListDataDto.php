<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ArrayList;

class StaffMenuTreeListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, StaffMenuRowDto>
     */
    #[ApiProperty(description: '菜单树')]
    #[ArrayList(itemClass: StaffMenuRowDto::class)]
    protected array $list = [];

    /**
     * @return array<int, StaffMenuRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, StaffMenuRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof StaffMenuRowDto)) {
            throw new InvalidArgumentException('list items must be instances of StaffMenuRowDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(StaffMenuRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<StaffMenuRowDto|array<string, mixed>> $items
     */
    public static function fromItems(array $items): self
    {
        $dto = new self();
        foreach ($items as $item) {
            $dto->addListItem($item instanceof StaffMenuRowDto
                ? $item
                : StaffMenuRowDto::fromEntityRow($item));
        }
        $dto->setTotal(count($dto->getList()));

        return $dto;
    }
}
