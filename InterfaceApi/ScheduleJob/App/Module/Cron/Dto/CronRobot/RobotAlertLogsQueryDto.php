<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class RobotAlertLogsQueryDto extends AbstractDto
{
    #[ApiProperty(description: '当前页码，从 1 开始')]
    protected int $page = 1;

    #[ApiProperty(description: '每页条数')]
    protected int $pageSize = 20;

    #[ApiProperty(description: '机器人 ID；null 表示不过滤')]
    protected ?int $robotId = null;

    #[ApiProperty(description: '告警类型；null 表示不过滤')]
    protected ?int $alertType = null;

    #[ApiProperty(description: '投递状态；null 表示不过滤')]
    protected ?int $status = null;

    #[ApiProperty(description: '节点 ID；null 表示不过滤')]
    protected ?int $nodeId = null;

    #[ApiProperty(description: '开始时间（含）')]
    protected ?string $startTime = null;

    #[ApiProperty(description: '结束时间（含）')]
    protected ?string $endTime = null;

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): static
    {
        $this->page = max(1, $page);

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): static
    {
        $this->pageSize = max(1, $pageSize);

        return $this;
    }

    public function getOffset(): int
    {
        return ($this->getPage() - 1) * $this->getPageSize();
    }

    public function getRobotId(): ?int
    {
        return $this->robotId;
    }

    public function setRobotId(?int $robotId): static
    {
        $this->robotId = $robotId !== null && $robotId > 0 ? $robotId : null;

        return $this;
    }

    public function getAlertType(): ?int
    {
        return $this->alertType;
    }

    public function setAlertType(?int $alertType): static
    {
        $this->alertType = $alertType !== null && $alertType > 0 ? $alertType : null;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status !== null && $status > 0 ? $status : null;

        return $this;
    }

    public function getNodeId(): ?int
    {
        return $this->nodeId;
    }

    public function setNodeId(?int $nodeId): static
    {
        $this->nodeId = $nodeId !== null && $nodeId > 0 ? $nodeId : null;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(?string $startTime): static
    {
        $this->startTime = $startTime !== null && trim($startTime) !== '' ? trim($startTime) : null;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(?string $endTime): static
    {
        $this->endTime = $endTime !== null && trim($endTime) !== '' ? trim($endTime) : null;

        return $this;
    }
}
