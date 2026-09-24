<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\DashboardOverviewDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class DashboardOverviewResponse extends BaseResponse
{
    #[ApiProperty(description: '仪表盘概览 data')]
    protected DashboardOverviewDto $data;

    public function __construct(DashboardOverviewDto $overview)
    {
        $this->data = $overview;
    }

    public function getData(): DashboardOverviewDto
    {
        return $this->data;
    }

    /**
     * @param DashboardOverviewDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof DashboardOverviewDto) {
            throw new InvalidArgumentException('data must be DashboardOverviewDto');
        }
        $this->data = $data;

        return $this;
    }
}
