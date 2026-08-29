<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Http\BaseResponse;
use App\Module\Cron\Dto\CronTaskManager\RunOnceQueuedDto;

class RunOnceQueuedResponse extends BaseResponse
{
    protected RunOnceQueuedDto $result;

    public function __construct(RunOnceQueuedDto $result)
    {
        $this->result = $result;
    }

    public function getData(): array
    {
        return $this->result->toDeepArray();
    }
}
