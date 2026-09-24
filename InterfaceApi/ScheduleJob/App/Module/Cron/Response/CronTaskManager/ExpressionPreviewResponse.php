<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExpressionPreviewResultDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class ExpressionPreviewResponse extends BaseResponse
{
    #[ApiProperty(description: '表达式预览 data')]
    protected ExpressionPreviewResultDto $data;

    public function __construct(ExpressionPreviewResultDto $result)
    {
        $this->data = $result;
    }

    public function getData(): ExpressionPreviewResultDto
    {
        return $this->data;
    }

    /**
     * @param ExpressionPreviewResultDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ExpressionPreviewResultDto) {
            throw new InvalidArgumentException('data must be ExpressionPreviewResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
