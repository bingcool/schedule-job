<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronRobot;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Cron\Dto\CronRobot\CronRobotListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class CronRobotListResponse extends BaseListResponse
{
    #[ApiProperty(description: '机器人列表 data')]
    protected CronRobotListDataDto $data;

    /**
     * @param CronRobotListDataDto|list<\App\Module\Cron\Dto\CronRobot\CronRobotRowDto|array<string, mixed>> $list
     */
    public function __construct(CronRobotListDataDto|array $list)
    {
        $this->data = $list instanceof CronRobotListDataDto
            ? $list
            : CronRobotListDataDto::fromItems($list);
    }

    public function getData(): CronRobotListDataDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof CronRobotListDataDto) {
            throw new InvalidArgumentException('data must be CronRobotListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
