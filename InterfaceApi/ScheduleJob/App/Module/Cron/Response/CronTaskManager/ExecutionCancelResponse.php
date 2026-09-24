<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionCancelResultDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class ExecutionCancelResponse extends BaseResponse
{
    #[ApiProperty(description: '取消执行结果 data')]
    protected ExecutionCancelResultDto $data;

    public function __construct(ExecutionCancelResultDto $result)
    {
        $this->data = $result;
    }

    public function getData(): ExecutionCancelResultDto
    {
        return $this->data;
    }

    /**
     * @param ExecutionCancelResultDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ExecutionCancelResultDto) {
            throw new InvalidArgumentException('data must be ExecutionCancelResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
