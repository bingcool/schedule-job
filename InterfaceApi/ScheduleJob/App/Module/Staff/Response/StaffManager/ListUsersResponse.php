<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\ScheduleJob\App\Module\Common\Http\BasePageResultResponse;

class ListUsersResponse extends BasePageResultResponse
{
    #[ApiProperty(description: '分页 data')]
    protected ListUsersPageResult $data;

    public function __construct(ListUsersPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): ListUsersPageResult
    {
        return $this->data;
    }

    /**
     * @param ListUsersPageResult $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ListUsersPageResult) {
            throw new InvalidArgumentException('data must be ListUsersPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
