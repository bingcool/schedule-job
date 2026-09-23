<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\StaffMenuRowDto;
use Swoolefy\Http\BaseResponse;

class StaffMenuRowResponse extends BaseResponse
{
    protected StaffMenuRowDto $row;

    public function __construct(StaffMenuRowDto|array $attributes)
    {
        $this->row = $attributes instanceof StaffMenuRowDto
            ? $attributes
            : StaffMenuRowDto::fromEntityRow($attributes);
    }

    public function getData(): array
    {
        return $this->row->toDeepArray();
    }
}
