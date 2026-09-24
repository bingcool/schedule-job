<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffMenuSortAckDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class StaffMenuSortAckResponse extends BaseResponse
{
    #[ApiProperty(description: '菜单排序确认 data')]
    protected StaffMenuSortAckDto $data;

    /**
     * @param array<int, int> $ids
     */
    public function __construct(int $parentId, array $ids)
    {
        $this->data = StaffMenuSortAckDto::of($parentId, $ids);
    }

    public function getData(): StaffMenuSortAckDto
    {
        return $this->data;
    }

    /**
     * @param StaffMenuSortAckDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof StaffMenuSortAckDto) {
            throw new InvalidArgumentException('data must be StaffMenuSortAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
