<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\ExpressionPreviewResultDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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

    public function setData($data): static
    {
        if (!$data instanceof ExpressionPreviewResultDto) {
            throw new InvalidArgumentException('data must be ExpressionPreviewResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
