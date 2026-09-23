<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\CronAgentNodeGroupRowDto;
use Swoolefy\Http\BaseResponse;

class CronNodeGroupRowResponse extends BaseResponse
{
    protected CronAgentNodeGroupRowDto $data;

    public function __construct(CronAgentNodeGroupRowDto|array $attributes)
    {
        $this->data = $attributes instanceof CronAgentNodeGroupRowDto
            ? $attributes
            : CronAgentNodeGroupRowDto::fromEntityRow($attributes);
    }

    public function getData(): CronAgentNodeGroupRowDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        $this->data = $data;

        return $this;
    }
}
