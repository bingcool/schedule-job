<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BasePageResultResponse;

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

    public function setData($data): static
    {
        if (!$data instanceof ListRolesPageResult) {
            throw new InvalidArgumentException('data must be ListRolesPageResult');
        }
        $this->data = $data;

        return $this;
    }
}
