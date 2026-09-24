<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\RunOnceQueuedDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class RunOnceQueuedResponse extends BaseResponse
{
    #[ApiProperty(description: 'RunOnce 入队结果 data')]
    protected RunOnceQueuedDto $data;

    public function __construct(RunOnceQueuedDto $result)
    {
        $this->data = $result;
    }

    public function getData(): RunOnceQueuedDto
    {
        return $this->data;
    }

    /**
     * @param RunOnceQueuedDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof RunOnceQueuedDto) {
            throw new InvalidArgumentException('data must be RunOnceQueuedDto');
        }
        $this->data = $data;

        return $this;
    }
}
