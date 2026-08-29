<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Http\BaseResponse;
use App\Module\Cron\Dto\CronTaskManager\ExecutionDetailDto;

class ExecutionDetailResponse extends BaseResponse
{
    protected ExecutionDetailDto $detail;

    public function __construct(ExecutionDetailDto $detail)
    {
        $this->detail = $detail;
    }

    public function getData(): array
    {
        return $this->detail->toDeepArray();
    }
}
