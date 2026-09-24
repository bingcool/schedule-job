<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\StaffUserRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class StaffUserRowResponse extends BaseResponse
{
    #[ApiProperty(description: '用户详情 data')]
    protected StaffUserRowDto $data;

    public function __construct(StaffUserRowDto|array $attributes)
    {
        $this->data = $attributes instanceof StaffUserRowDto
            ? $attributes
            : StaffUserRowDto::fromEntityRow($attributes);
    }

    public function getData(): StaffUserRowDto
    {
        return $this->data;
    }

    /**
     * @param StaffUserRowDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof StaffUserRowDto) {
            throw new InvalidArgumentException('data must be StaffUserRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
