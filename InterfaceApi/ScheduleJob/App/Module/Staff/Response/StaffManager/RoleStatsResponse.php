<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\RoleStatsDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class RoleStatsResponse extends BaseResponse
{
    #[ApiProperty(description: '角色统计 data')]
    protected RoleStatsDto $data;

    public function __construct(RoleStatsDto $stats)
    {
        $this->data = $stats;
    }

    public function getData(): RoleStatsDto
    {
        return $this->data;
    }

    /**
     * @param RoleStatsDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof RoleStatsDto) {
            throw new InvalidArgumentException('data must be RoleStatsDto');
        }
        $this->data = $data;

        return $this;
    }
}
