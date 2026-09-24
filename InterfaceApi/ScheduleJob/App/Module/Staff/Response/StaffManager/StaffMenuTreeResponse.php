<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Http\BaseListResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffMenuTreeListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;

class StaffMenuTreeResponse extends BaseListResponse
{
    #[ApiProperty(description: '菜单树 data')]
    protected StaffMenuTreeListDataDto $data;

    /**
     * @param StaffMenuTreeListDataDto|list<\InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffMenuRowDto|array<string, mixed>> $list
     */
    public function __construct(StaffMenuTreeListDataDto|array $list)
    {
        $this->data = $list instanceof StaffMenuTreeListDataDto
            ? $list
            : StaffMenuTreeListDataDto::fromItems($list);
    }

    public function getData(): StaffMenuTreeListDataDto
    {
        return $this->data;
    }

    /**
     * @param StaffMenuTreeListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof StaffMenuTreeListDataDto) {
            throw new InvalidArgumentException('data must be StaffMenuTreeListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
