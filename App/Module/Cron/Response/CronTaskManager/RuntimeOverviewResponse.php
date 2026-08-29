<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Http\BaseResponse;
use App\Module\Cron\Dto\CronTaskManager\RuntimeOverviewDto;

class RuntimeOverviewResponse extends BaseResponse
{
    protected RuntimeOverviewDto $overview;

    public function __construct(RuntimeOverviewDto $overview)
    {
        $this->overview = $overview;
    }

    public function getData(): array
    {
        return $this->overview->toDeepArray();
    }
}
