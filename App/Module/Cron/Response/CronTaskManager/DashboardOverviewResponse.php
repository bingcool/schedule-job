<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Http\BaseResponse;
use App\Module\Cron\Dto\CronTaskManager\DashboardOverviewDto;

class DashboardOverviewResponse extends BaseResponse
{
    protected DashboardOverviewDto $overview;

    public function __construct(DashboardOverviewDto $overview)
    {
        $this->overview = $overview;
    }

    public function getData(): array
    {
        return $this->overview->toDeepArray();
    }
}
