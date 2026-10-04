<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffNodeGroupBriefDto extends AbstractDto
{
    #[ApiProperty(description: '节点组 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '节点组名称')]
    protected string $groupName = '';

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
}
