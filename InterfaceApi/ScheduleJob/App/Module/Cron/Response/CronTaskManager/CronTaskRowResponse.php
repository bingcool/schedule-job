<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskRowDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronTaskRowResponse extends BaseResponse
{
    #[ApiProperty(description: '任务详情 data')]
    protected CronTaskRowDto $data;

    public function __construct(CronTaskRowDto|array $attributes)
    {
        $this->data = $attributes instanceof CronTaskRowDto
            ? $attributes
            : CronTaskRowDto::fromEntityRow($attributes);
    }

    public function getData(): CronTaskRowDto
    {
        return $this->data;
    }

    /**
     * @param CronTaskRowDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronTaskRowDto) {
            throw new InvalidArgumentException('data must be CronTaskRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
