<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\ExecutionCancelResultDto;
use Swoolefy\Http\BaseResponse;

class ExecutionCancelResponse extends BaseResponse
{
    protected ExecutionCancelResultDto $result;

    public function __construct(ExecutionCancelResultDto $result)
    {
        $this->result = $result;
    }

    public function getData(): array
    {
        return $this->result->toDeepArray();
    }
}
