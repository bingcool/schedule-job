<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronRobot;

use App\Module\Cron\Dto\CronRobot\CronRobotRowDto;
use Swoolefy\Http\BaseResponse;

class CronRobotRowResponse extends BaseResponse
{
    protected CronRobotRowDto $data;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->data = CronRobotRowDto::fromEntityRow($attributes);
    }

    public function getData(): CronRobotRowDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        $this->data = $data;

        return $this;
    }
}
