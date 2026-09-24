<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffRoleRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class StaffRoleRowResponse extends BaseResponse
{
    #[ApiProperty(description: '角色详情 data')]
    protected StaffRoleRowDto $data;

    public function __construct(StaffRoleRowDto|array $attributes)
    {
        $this->data = $attributes instanceof StaffRoleRowDto
            ? $attributes
            : StaffRoleRowDto::fromEntityRow($attributes);
    }

    public function getData(): StaffRoleRowDto
    {
        return $this->data;
    }

    /**
     * @param StaffRoleRowDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof StaffRoleRowDto) {
            throw new InvalidArgumentException('data must be StaffRoleRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
