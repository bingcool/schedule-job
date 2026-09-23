<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\StaffMenuRowDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffMenuRowResponse extends BaseResponse
{
    #[ApiProperty(description: '菜单详情 data')]
    protected StaffMenuRowDto $data;

    public function __construct(StaffMenuRowDto|array $attributes)
    {
        $this->data = $attributes instanceof StaffMenuRowDto
            ? $attributes
            : StaffMenuRowDto::fromEntityRow($attributes);
    }

    public function getData(): StaffMenuRowDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof StaffMenuRowDto) {
            throw new InvalidArgumentException('data must be StaffMenuRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
