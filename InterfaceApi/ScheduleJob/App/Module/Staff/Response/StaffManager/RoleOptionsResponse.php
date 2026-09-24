<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Http\BaseListResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\RoleOptionsListDataDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;

class RoleOptionsResponse extends BaseListResponse
{
    #[ApiProperty(description: '角色选项 data')]
    protected RoleOptionsListDataDto $data;

    /**
     * @param RoleOptionsListDataDto|list<\InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole\StaffRoleOptionDto> $list
     */
    public function __construct(RoleOptionsListDataDto|array $list)
    {
        $this->data = $list instanceof RoleOptionsListDataDto
            ? $list
            : RoleOptionsListDataDto::fromItems($list);
    }

    public function getData(): RoleOptionsListDataDto
    {
        return $this->data;
    }

    /**
     * @param RoleOptionsListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof RoleOptionsListDataDto) {
            throw new InvalidArgumentException('data must be RoleOptionsListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
