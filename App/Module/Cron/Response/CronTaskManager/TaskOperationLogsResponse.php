<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Http\BasePageResultResponse;

class TaskOperationLogsResponse extends BasePageResultResponse
{
    protected TaskOperationLogsPageResult $data;

    public function __construct(TaskOperationLogsPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): TaskOperationLogsPageResult
    {
        return $this->data;
    }
}
