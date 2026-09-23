<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\BatchStatusResultDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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

    public function setData($data): static
    {
        if (!$data instanceof BatchStatusResultDto) {
            throw new InvalidArgumentException('data must be BatchStatusResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
