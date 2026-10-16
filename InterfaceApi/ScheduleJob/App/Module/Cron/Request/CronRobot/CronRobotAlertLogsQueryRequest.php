<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BasePageRequest;

/**
 * 机器人告警投递记录分页查询。
 */
class CronRobotAlertLogsQueryRequest extends BasePageRequest
{
    #[ApiProperty(description: '机器人 ID，不传则不过滤')]
    #[ValidationRule(rule: 'nullable|int', message: 'robotId 必须是整数')]
    #[StringToInt]
    protected ?int $robotId = null;

    #[ApiProperty(description: '告警类型：1-任务失败 2-任务超时 3-节点失联 4-节点上线')]
    #[ValidationRule(rule: 'nullable|int', message: 'alertType 必须是整数')]
    #[StringToInt]
    protected ?int $alertType = null;

    #[ApiProperty(description: '投递状态：1-成功 2-失败 3-跳过')]
    #[ValidationRule(rule: 'nullable|int', message: 'status 必须是整数')]
    #[StringToInt]
    protected ?int $status = null;

    #[ApiProperty(description: '节点 ID，不传则不过滤')]
    #[ValidationRule(rule: 'nullable|int', message: 'nodeId 必须是整数')]
    #[StringToInt]
    protected ?int $nodeId = null;

    #[ApiProperty(description: '开始时间（含）')]
    protected ?string $startTime = null;

    #[ApiProperty(description: '结束时间（含）')]
    protected ?string $endTime = null;

    public function getRobotId(): ?int
    {
        return $this->robotId;
    }

    public function setRobotId(?int $robotId): static
    {
        $this->robotId = $robotId;

        return $this;
    }

    public function getAlertType(): ?int
    {
        return $this->alertType;
    }

    public function setAlertType(?int $alertType): static
    {
        $this->alertType = $alertType;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNodeId(): ?int
    {
        return $this->nodeId;
    }

    public function setNodeId(?int $nodeId): static
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime !== null && trim($this->startTime) !== '' ? trim($this->startTime) : null;
    }

    public function setStartTime(?string $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime !== null && trim($this->endTime) !== '' ? trim($this->endTime) : null;
    }

    public function setEndTime(?string $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }
}
