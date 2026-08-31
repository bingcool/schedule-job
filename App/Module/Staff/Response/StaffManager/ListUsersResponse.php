<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Http\BasePageResultResponse;

class ListUsersResponse extends BasePageResultResponse
{
    protected ListUsersPageResult $data;

    public function __construct(ListUsersPageResult $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        $items = [];
        foreach ($this->data->getList() as $row) {
            $items[] = $row->toDeepArray();
        }

        return [
            'items' => $items,
            'list' => $items,
            'page' => $this->data->getPage(),
            'pageSize' => $this->data->getPageSize(),
            'total' => $this->data->getTotal(),
        ];
    }
}
