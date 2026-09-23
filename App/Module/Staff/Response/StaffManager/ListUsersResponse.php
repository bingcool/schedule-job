<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BasePageResultResponse;

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

    public function setData($data): static
    {
        if (!$data instanceof ListUsersPageResult) {
            throw new InvalidArgumentException('data must be ListUsersPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
