<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronRobot;

use App\Module\Cron\Dto\CronRobot\CronRobotRowDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class CronRobotRowResponse extends BaseResponse
{
    #[ApiProperty(description: '机器人详情 data')]
    protected CronRobotRowDto $data;

    public function __construct(CronRobotRowDto|array $attributes)
    {
        $this->data = $attributes instanceof CronRobotRowDto
            ? $attributes
            : CronRobotRowDto::fromEntityRow($attributes);
    }

    public function getData(): CronRobotRowDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof CronRobotRowDto) {
            throw new InvalidArgumentException('data must be CronRobotRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
