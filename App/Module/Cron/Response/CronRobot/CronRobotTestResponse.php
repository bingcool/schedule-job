<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronRobot;

use App\Module\Cron\Dto\CronRobot\CronRobotTestResultDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

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

    public function setData($data): static
    {
        if (!$data instanceof CronRobotTestResultDto) {
            throw new InvalidArgumentException('data must be CronRobotTestResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
