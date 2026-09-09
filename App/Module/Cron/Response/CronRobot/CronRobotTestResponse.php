<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronRobot;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class CronRobotTestResponse extends BaseResponse
{
    #[ApiProperty(description: '测试是否成功')]
    protected bool $ok = false;

    #[ApiProperty(description: '失败原因，成功为空')]
    protected string $error = '';

    public function __construct(bool $ok, string $error)
    {
        $this->ok = $ok;
        $this->error = $error;
    }

    public function getData(): array
    {
        return [
            'ok' => $this->ok,
            'error' => $this->error,
        ];
    }
}
