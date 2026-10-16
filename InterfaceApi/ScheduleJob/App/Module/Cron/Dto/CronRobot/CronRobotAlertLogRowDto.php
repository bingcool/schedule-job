<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class CronRobotAlertLogRowDto extends AbstractDto
{
    #[ApiProperty(description: '投递记录 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '机器人 ID')]
    protected int $robotId = 0;

    #[ApiProperty(description: '机器人名称')]
    protected string $robotName = '';

    #[ApiProperty(description: '平台：1-企微 2-钉钉 3-飞书')]
    protected int $platform = 0;

    #[ApiProperty(description: '告警类型：1-任务失败 2-任务超时 3-节点失联 4-节点上线')]
    protected int $alertType = 0;

    #[ApiProperty(description: '告警类型名称')]
    protected string $alertTypeName = '';

    #[ApiProperty(description: '投递状态：1-成功 2-失败 3-跳过')]
    protected int $status = 0;

    #[ApiProperty(description: '投递状态名称')]
    protected string $statusName = '';

    #[ApiProperty(description: '跳过原因码')]
    protected string $skipReason = '';

    #[ApiProperty(description: 'Webhook HTTP 状态码')]
    protected int $httpStatus = 0;

    #[ApiProperty(description: '失败/跳过说明')]
    protected string $errorMessage = '';

    #[ApiProperty(description: 'Webhook 调用结束时间')]
    protected string $sentAt = '';

    #[ApiProperty(description: '任务 ID；节点存活告警为 0')]
    protected int $cronId = 0;

    #[ApiProperty(description: '任务名称')]
    protected string $taskName = '';

    #[ApiProperty(description: '执行记录 ID；节点存活告警为 0')]
    protected int $executionId = 0;

    #[ApiProperty(description: '节点 ID')]
    protected int $nodeId = 0;

    #[ApiProperty(description: '节点名称')]
    protected string $nodeName = '';

    #[ApiProperty(description: '执行批次')]
    protected string $execBatchId = '';

    #[ApiProperty(description: '记录创建时间')]
    protected string $createdAt = '';

    public function getId(): int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getRobotId(): int { return $this->robotId; }
    public function setRobotId(int $robotId): static { $this->robotId = $robotId; return $this; }
    public function getRobotName(): string { return $this->robotName; }
    public function setRobotName(string $robotName): static { $this->robotName = $robotName; return $this; }
    public function getPlatform(): int { return $this->platform; }
    public function setPlatform(int $platform): static { $this->platform = $platform; return $this; }
    public function getAlertType(): int { return $this->alertType; }
    public function setAlertType(int $alertType): static { $this->alertType = $alertType; return $this; }
    public function getAlertTypeName(): string { return $this->alertTypeName; }
    public function setAlertTypeName(string $alertTypeName): static { $this->alertTypeName = $alertTypeName; return $this; }
    public function getStatus(): int { return $this->status; }
    public function setStatus(int $status): static { $this->status = $status; return $this; }
    public function getStatusName(): string { return $this->statusName; }
    public function setStatusName(string $statusName): static { $this->statusName = $statusName; return $this; }
    public function getSkipReason(): string { return $this->skipReason; }
    public function setSkipReason(string $skipReason): static { $this->skipReason = $skipReason; return $this; }
    public function getHttpStatus(): int { return $this->httpStatus; }
    public function setHttpStatus(int $httpStatus): static { $this->httpStatus = $httpStatus; return $this; }
    public function getErrorMessage(): string { return $this->errorMessage; }
    public function setErrorMessage(string $errorMessage): static { $this->errorMessage = $errorMessage; return $this; }
    public function getSentAt(): string { return $this->sentAt; }
    public function setSentAt(string $sentAt): static { $this->sentAt = $sentAt; return $this; }
    public function getCronId(): int { return $this->cronId; }
    public function setCronId(int $cronId): static { $this->cronId = $cronId; return $this; }
    public function getTaskName(): string { return $this->taskName; }
    public function setTaskName(string $taskName): static { $this->taskName = $taskName; return $this; }
    public function getExecutionId(): int { return $this->executionId; }
    public function setExecutionId(int $executionId): static { $this->executionId = $executionId; return $this; }
    public function getNodeId(): int { return $this->nodeId; }
    public function setNodeId(int $nodeId): static { $this->nodeId = $nodeId; return $this; }
    public function getNodeName(): string { return $this->nodeName; }
    public function setNodeName(string $nodeName): static { $this->nodeName = $nodeName; return $this; }
    public function getExecBatchId(): string { return $this->execBatchId; }
    public function setExecBatchId(string $execBatchId): static { $this->execBatchId = $execBatchId; return $this; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function setCreatedAt(string $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
