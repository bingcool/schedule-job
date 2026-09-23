<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BasePageResultResponse;

class TaskOperationLogsResponse extends BasePageResultResponse
{
    #[ApiProperty(description: '分页 data')]
    protected TaskOperationLogsPageResult $data;

    public function __construct(TaskOperationLogsPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): TaskOperationLogsPageResult
    {
        return $this->data;
    }

    /**
     * @param TaskOperationLogsPageResult $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof TaskOperationLogsPageResult) {
            throw new InvalidArgumentException('data must be TaskOperationLogsPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
