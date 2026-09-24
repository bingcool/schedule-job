<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class CronAgentHeartbeatRequest extends BaseRequest
{
    #[ApiProperty(description: '节点 ID')]
    #[ValidationRule(rule: 'required|int', message: 'nodeId 不能为空')]
    #[StringToInt]
    protected int $nodeId = 0;

    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    public function setNodeId(int $nodeId): static
    {
        $this->nodeId = $nodeId;

        return $this;
    }
}
