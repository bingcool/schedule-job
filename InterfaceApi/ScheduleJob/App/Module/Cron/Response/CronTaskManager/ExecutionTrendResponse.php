<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Http\BaseListResponse;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionTrendListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;

class ExecutionTrendResponse extends BaseListResponse
{
    #[ApiProperty(description: '执行趋势 data')]
    protected ExecutionTrendListDataDto $data;

    /**
     * @param ExecutionTrendListDataDto|list<\InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionTrendBucketDto> $buckets
     */
    public function __construct(ExecutionTrendListDataDto|array $buckets)
    {
        $this->data = $buckets instanceof ExecutionTrendListDataDto
            ? $buckets
            : ExecutionTrendListDataDto::fromItems($buckets);
    }

    public function getData(): ExecutionTrendListDataDto
    {
        return $this->data;
    }

    /**
     * @param ExecutionTrendListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ExecutionTrendListDataDto) {
            throw new InvalidArgumentException('data must be ExecutionTrendListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
