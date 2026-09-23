<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Cron\Dto\CronTaskManager\TaskOperationOperatorOptionsListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class TaskOperationOperatorOptionsResponse extends BaseListResponse
{
    #[ApiProperty(description: '操作人选项 data')]
    protected TaskOperationOperatorOptionsListDataDto $data;

    /**
     * @param TaskOperationOperatorOptionsListDataDto|list<\App\Module\Cron\Dto\CronTaskManager\TaskOperationOperatorOptionDto> $list
     */
    public function __construct(TaskOperationOperatorOptionsListDataDto|array $list)
    {
        $this->data = $list instanceof TaskOperationOperatorOptionsListDataDto
            ? $list
            : TaskOperationOperatorOptionsListDataDto::fromItems($list);
    }

    public function getData(): TaskOperationOperatorOptionsListDataDto
    {
        return $this->data;
    }

    /**
     * @param TaskOperationOperatorOptionsListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof TaskOperationOperatorOptionsListDataDto) {
            throw new InvalidArgumentException('data must be TaskOperationOperatorOptionsListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
