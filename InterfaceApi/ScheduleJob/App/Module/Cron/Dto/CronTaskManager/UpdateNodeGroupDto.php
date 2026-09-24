<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 更新节点分组入参。
 */
class UpdateNodeGroupDto extends AbstractDto
{
    #[ApiProperty(description: '分组 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '分组名称')]
    protected string $groupName = '';

    #[ApiProperty(description: '备注')]
    protected string $remark = '';

    #[ApiProperty(description: '是否在本次请求中更新 robotId')]
    protected bool $robotIdProvided = false;

    #[ApiProperty(description: '绑定的机器人 ID；0=不告警')]
    protected int $robotId = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): static
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }

    public function setRemark(string $remark): static
    {
        $this->remark = $remark;

        return $this;
    }

    public function isRobotIdProvided(): bool
    {
        return $this->robotIdProvided;
    }

    public function setRobotIdProvided(bool $robotIdProvided): static
    {
        $this->robotIdProvided = $robotIdProvided;

        return $this;
    }

    public function getRobotId(): int
    {
        return $this->robotId;
    }

    public function setRobotId(int $robotId): static
    {
        $this->robotId = $robotId;

        return $this;
    }
}
