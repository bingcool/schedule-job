<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\RoleStatsDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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
