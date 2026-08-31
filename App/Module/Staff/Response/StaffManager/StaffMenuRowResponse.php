<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffManager\StaffMenuRowDto;
use Swoolefy\Http\BaseResponse;

class StaffMenuRowResponse extends BaseResponse
{
    protected StaffMenuRowDto $row;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->row = StaffMenuRowDto::fromEntityRow($attributes);
    }

    public function getData(): array
    {
        return $this->row->toDeepArray();
    }
}
