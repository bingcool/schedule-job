<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\RoleStatsDto;
use Swoolefy\Http\BaseResponse;

class RoleStatsResponse extends BaseResponse
{
    protected RoleStatsDto $stats;

    public function __construct(RoleStatsDto $stats)
    {
        $this->stats = $stats;
    }

    public function getData(): array
    {
        return $this->stats->toDeepArray();
    }
}
