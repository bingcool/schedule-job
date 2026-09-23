<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BasePageResultResponse;

class ListTasksResponse extends BasePageResultResponse
{
    #[ApiProperty(description: '分页 data')]
    protected ListTasksPageResult $data;

    public function __construct(ListTasksPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): ListTasksPageResult
    {
        return $this->data;
    }

    /**
     * @param ListTasksPageResult $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ListTasksPageResult) {
            throw new InvalidArgumentException('data must be ListTasksPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
