<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Http\BaseListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\TaskCreatorOptionsListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;

class TaskCreatorOptionsResponse extends BaseListResponse
{
    #[ApiProperty(description: '创建人选项 data')]
    protected TaskCreatorOptionsListDataDto $data;

    /**
     * @param TaskCreatorOptionsListDataDto|list<\InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\TaskCreatorOptionDto> $list
     */
    public function __construct(TaskCreatorOptionsListDataDto|array $list)
    {
        $this->data = $list instanceof TaskCreatorOptionsListDataDto
            ? $list
            : TaskCreatorOptionsListDataDto::fromItems($list);
    }

    public function getData(): TaskCreatorOptionsListDataDto
    {
        return $this->data;
    }

    /**
     * @param TaskCreatorOptionsListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof TaskCreatorOptionsListDataDto) {
            throw new InvalidArgumentException('data must be TaskCreatorOptionsListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
