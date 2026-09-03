<?php

declare(strict_types=1);

namespace App\Module\Cron\Request\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class CronAgentTasksQueryRequest extends BaseRequest
{
    #[ApiProperty(description: '节点 ID')]
    #[ValidationRule(rule: 'required|int', message: 'nodeId 不能为空')]
    #[StringToInt]
    protected int $nodeId = 0;

    #[ApiProperty(description: '节点 API Key')]
    #[ValidationRule(rule: 'required|string', message: 'apiKey 不能为空')]
    protected string $apiKey = '';

    #[ApiProperty(description: '执行类型：1 shell，2 http；省略则返回全部')]
    protected ?int $execType = null;

    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    public function setNodeId(int $nodeId): static
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getExecType(): ?int
    {
        return $this->execType;
    }

    public function setExecType(?int $execType): static
    {
        $this->execType = $execType;

        return $this;
    }
}
