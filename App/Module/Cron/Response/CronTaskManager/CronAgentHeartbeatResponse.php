<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\AgentHeartbeatResultDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class CronAgentHeartbeatResponse extends BaseResponse
{
    #[ApiProperty(description: '心跳确认 data')]
    protected AgentHeartbeatResultDto $data;

    public function __construct(AgentHeartbeatResultDto $result)
    {
        $this->data = $result;
    }

    public function getData(): AgentHeartbeatResultDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof AgentHeartbeatResultDto) {
            throw new InvalidArgumentException('data must be AgentHeartbeatResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
