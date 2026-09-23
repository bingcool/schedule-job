<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\DashboardOverviewDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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
