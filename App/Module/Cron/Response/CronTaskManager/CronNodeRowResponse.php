<?php

declare(strict_types=1);


namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto;
use Swoolefy\Http\BaseResponse;

class CronNodeRowResponse extends BaseResponse
{
    protected CronAgentNodeRowDto $data;

    public function __construct(CronAgentNodeRowDto|array $attributes)
    {
        $this->data = $attributes instanceof CronAgentNodeRowDto
            ? $attributes
            : CronAgentNodeRowDto::fromEntityRow($attributes);
    }

    public function getData(): CronAgentNodeRowDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        $this->data = $data;

        return $this;
    }
}
