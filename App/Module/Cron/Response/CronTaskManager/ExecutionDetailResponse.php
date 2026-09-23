<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\ExecutionDetailDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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

    public function setData($data): static
    {
        if (!$data instanceof ExecutionDetailDto) {
            throw new InvalidArgumentException('data must be ExecutionDetailDto');
        }
        $this->data = $data;

        return $this;
    }
}
