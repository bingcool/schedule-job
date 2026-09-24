<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\BatchStatusResultDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class BatchStatusResponse extends BaseResponse
{
    #[ApiProperty(description: '批量启停结果 data')]
    protected BatchStatusResultDto $data;

    public function __construct(BatchStatusResultDto $result)
    {
        $this->data = $result;
    }

    public function getData(): BatchStatusResultDto
    {
        return $this->data;
    }

    /**
     * @param BatchStatusResultDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof BatchStatusResultDto) {
            throw new InvalidArgumentException('data must be BatchStatusResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
