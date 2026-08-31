<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffManager\StaffUserRowDto;
use Swoolefy\Http\BaseResponse;

class StaffUserRowResponse extends BaseResponse
{
    protected StaffUserRowDto $row;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->row = StaffUserRowDto::fromEntityRow($attributes);
    }

    public function getData(): array
    {
        return $this->row->toDeepArray();
    }
}
