<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Http\BaseResponse;

class RoleOptionsResponse extends BaseResponse
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $list;

    /**
     * @param array<int, array<string, mixed>> $list
     */
    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function getData(): array
    {
        return [
            'list' => $this->list,
            'total' => count($this->list),
        ];
    }
}
