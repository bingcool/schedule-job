<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffManager\StaffRoleRowDto;
use Swoolefy\Http\BaseResponse;

class StaffRoleRowResponse extends BaseResponse
{
    protected StaffRoleRowDto $row;

    /**
     * @var array<string, mixed>
     */
    protected array $extra;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->row = StaffRoleRowDto::fromEntityRow($attributes);
        $this->extra = $attributes;
    }

    public function getData(): array
    {
        $data = $this->row->toDeepArray();
        if (isset($this->extra['apiPermissions'])) {
            $data['apiPermissions'] = $this->extra['apiPermissions'];
        }
        if (isset($this->extra['taskPermissions'])) {
            $data['taskPermissions'] = $this->extra['taskPermissions'];
        }
        if (isset($this->extra['menus'])) {
            $data['menus'] = $this->extra['menus'];
        }

        return $data;
    }
}
