<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\CronTaskRowDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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

    public function setData($data): static
    {
        if (!$data instanceof CronTaskRowDto) {
            throw new InvalidArgumentException('data must be CronTaskRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
