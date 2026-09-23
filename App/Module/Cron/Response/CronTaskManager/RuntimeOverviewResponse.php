<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\RuntimeOverviewDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class RuntimeOverviewResponse extends BaseResponse
{
    #[ApiProperty(description: '运行时概览 data')]
    protected RuntimeOverviewDto $data;

    public function __construct(RuntimeOverviewDto $overview)
    {
        $this->data = $overview;
    }

    public function getData(): RuntimeOverviewDto
    {
        return $this->data;
    }

    /**
     * @param RuntimeOverviewDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof RuntimeOverviewDto) {
            throw new InvalidArgumentException('data must be RuntimeOverviewDto');
        }
        $this->data = $data;

        return $this;
    }
}
