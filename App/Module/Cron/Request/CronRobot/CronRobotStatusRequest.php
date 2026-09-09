<?php

declare(strict_types=1);

namespace App\Module\Cron\Request\CronRobot;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class CronRobotStatusRequest extends BaseRequest
{
    #[ApiProperty(description: '机器人 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    #[ApiProperty(description: '0-禁用 1-启用')]
    #[ValidationRule(rule: 'required|int', message: 'status 不能为空')]
    #[StringToInt]
    protected int $status = 1;

    public function getId(): int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getStatus(): int { return $this->status; }
    public function setStatus(int $status): static { $this->status = $status; return $this; }
}
