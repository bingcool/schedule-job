<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BasePageResultResponse;

class TaskLogsResponse extends BasePageResultResponse
{
    #[ApiProperty(description: '分页 data')]
    protected TaskLogsPageResult $data;

    public function __construct(TaskLogsPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): TaskLogsPageResult
    {
        return $this->data;
    }

    /**
     * @param TaskLogsPageResult $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof TaskLogsPageResult) {
            throw new InvalidArgumentException('data must be TaskLogsPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
