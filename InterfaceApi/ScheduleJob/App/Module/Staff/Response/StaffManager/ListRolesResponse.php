<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\ScheduleJob\App\Module\Common\Http\BasePageResultResponse;

class ListRolesResponse extends BasePageResultResponse
{
    #[ApiProperty(description: '分页 data')]
    protected ListRolesPageResult $data;

    public function __construct(ListRolesPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): ListRolesPageResult
    {
        return $this->data;
    }

    /**
     * @param ListRolesPageResult $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ListRolesPageResult) {
            throw new InvalidArgumentException('data must be ListRolesPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
