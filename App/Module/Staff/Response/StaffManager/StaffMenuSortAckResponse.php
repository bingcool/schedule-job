<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\StaffMenuSortAckDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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
