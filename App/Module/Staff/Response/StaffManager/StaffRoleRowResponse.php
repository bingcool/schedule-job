<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\StaffRoleRowDto;
use Swoolefy\Http\BaseResponse;

class StaffRoleRowResponse extends BaseResponse
{
    protected StaffRoleRowDto $row;

    public function __construct(StaffRoleRowDto|array $attributes)
    {
        $this->row = $attributes instanceof StaffRoleRowDto
            ? $attributes
            : StaffRoleRowDto::fromEntityRow($attributes);
    }

    public function getData(): array
    {
        return $this->row->toDeepArray();
    }
}
