<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\StaffRoleRowDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffRoleRowResponse extends BaseResponse
{
    #[ApiProperty(description: '角色详情 data')]
    protected StaffRoleRowDto $data;

    public function __construct(StaffRoleRowDto|array $attributes)
    {
        $this->data = $attributes instanceof StaffRoleRowDto
            ? $attributes
            : StaffRoleRowDto::fromEntityRow($attributes);
    }

    public function getData(): StaffRoleRowDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof StaffRoleRowDto) {
            throw new InvalidArgumentException('data must be StaffRoleRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
