<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffUser\StaffUserRowDto;
use Swoolefy\Http\BaseResponse;

class StaffUserRowResponse extends BaseResponse
{
    protected StaffUserRowDto $row;

    public function __construct(StaffUserRowDto|array $attributes)
    {
        $this->row = $attributes instanceof StaffUserRowDto
            ? $attributes
            : StaffUserRowDto::fromEntityRow($attributes);
    }

    public function getData(): array
    {
        return $this->row->toDeepArray();
    }
}
