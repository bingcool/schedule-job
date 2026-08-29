<?php

declare(strict_types=1);


namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Response\CronTaskManager\TaskLogsPageResult;
use Swoolefy\Http\BasePageResultResponse;

class TaskLogsResponse extends BasePageResultResponse
{
    protected TaskLogsPageResult $data;

    public function __construct(TaskLogsPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): TaskLogsPageResult
    {
        return $this->data;
    }
}
