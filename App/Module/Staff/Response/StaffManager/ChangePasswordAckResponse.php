<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffAuth\ChangePasswordAckDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class ChangePasswordAckResponse extends BaseResponse
{
    #[ApiProperty(description: '修改密码确认 data')]
    protected ChangePasswordAckDto $data;

    public function __construct(int $id)
    {
        $this->data = ChangePasswordAckDto::of($id);
    }

    public function getData(): ChangePasswordAckDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof ChangePasswordAckDto) {
            throw new InvalidArgumentException('data must be ChangePasswordAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
