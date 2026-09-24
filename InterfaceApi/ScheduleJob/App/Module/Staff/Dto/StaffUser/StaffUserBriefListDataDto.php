<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ArrayList;

class StaffUserBriefListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, StaffUserBriefDto>
     */
    #[ApiProperty(description: '用户简要列表')]
    #[ArrayList(itemClass: StaffUserBriefDto::class)]
    protected array $list = [];

    /**
     * @return array<int, StaffUserBriefDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, StaffUserBriefDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof StaffUserBriefDto)) {
            throw new InvalidArgumentException('list items must be instances of StaffUserBriefDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(StaffUserBriefDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<StaffUserBriefDto> $items
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
