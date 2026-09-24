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

    /**
     * @param array{id?: int, groupName?: string, group_name?: string} $row
     */
    public static function fromSlice(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->groupName = (string) ($row['groupName'] ?? $row['group_name'] ?? '');

        return $dto;
    }
}
