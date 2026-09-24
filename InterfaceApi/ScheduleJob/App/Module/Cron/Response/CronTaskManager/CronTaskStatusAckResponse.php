<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\TaskStatusAckDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronTaskStatusAckResponse extends BaseResponse
{
    #[ApiProperty(description: '任务状态确认 data')]
    protected TaskStatusAckDto $data;

    public function __construct(int $id, int $status)
    {
        $this->data = TaskStatusAckDto::of($id, $status);
    }

    public function getData(): TaskStatusAckDto
    {
        return $this->data;
    }

    /**
     * @param TaskStatusAckDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof TaskStatusAckDto) {
            throw new InvalidArgumentException('data must be TaskStatusAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
