<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronRobot;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotTestResultDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronRobotTestResponse extends BaseResponse
{
    #[ApiProperty(description: '连通测试结果 data')]
    protected CronRobotTestResultDto $data;

    public function __construct(bool $ok, string $error)
    {
        $this->data = CronRobotTestResultDto::of($ok, $error);
    }

    public function getData(): CronRobotTestResultDto
    {
        return $this->data;
    }

    /**
     * @param CronRobotTestResultDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronRobotTestResultDto) {
            throw new InvalidArgumentException('data must be CronRobotTestResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
