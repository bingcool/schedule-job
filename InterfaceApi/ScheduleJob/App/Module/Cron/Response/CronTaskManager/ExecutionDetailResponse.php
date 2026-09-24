<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionDetailDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class ExecutionDetailResponse extends BaseResponse
{
    #[ApiProperty(description: '执行详情 data')]
    protected ExecutionDetailDto $data;

    public function __construct(ExecutionDetailDto $detail)
    {
        $this->data = $detail;
    }

    public function getData(): ExecutionDetailDto
    {
        return $this->data;
    }

    /**
     * @param ExecutionDetailDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ExecutionDetailDto) {
            throw new InvalidArgumentException('data must be ExecutionDetailDto');
        }
        $this->data = $data;

        return $this;
    }
}
