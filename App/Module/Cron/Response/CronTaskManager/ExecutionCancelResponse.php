<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\ExecutionCancelResultDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class ExecutionCancelResponse extends BaseResponse
{
    #[ApiProperty(description: '取消执行结果 data')]
    protected ExecutionCancelResultDto $data;

    public function __construct(ExecutionCancelResultDto $result)
    {
        $this->data = $result;
    }

    public function getData(): ExecutionCancelResultDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof ExecutionCancelResultDto) {
            throw new InvalidArgumentException('data must be ExecutionCancelResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
