<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Http\BaseResponse;
use App\Module\Cron\Dto\CronTaskManager\BatchStatusResultDto;

class BatchStatusResponse extends BaseResponse
{
    protected BatchStatusResultDto $result;

    public function __construct(BatchStatusResultDto $result)
    {
        $this->result = $result;
    }

    public function getData(): array
    {
        return $this->result->toDeepArray();
    }
}
