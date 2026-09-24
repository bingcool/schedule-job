<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffMenuRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class StaffMenuRowResponse extends BaseResponse
{
    #[ApiProperty(description: '菜单详情 data')]
    protected StaffMenuRowDto $data;

    public function __construct(StaffMenuRowDto $data)
    {
        $this->data = $data;
    }

    public function getData(): StaffMenuRowDto
    {
        return $this->data;
    }

    /**
     * @param StaffMenuRowDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof StaffMenuRowDto) {
            throw new InvalidArgumentException('data must be StaffMenuRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
