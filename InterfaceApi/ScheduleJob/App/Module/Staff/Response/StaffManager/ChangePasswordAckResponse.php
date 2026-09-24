<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffAuth\ChangePasswordAckDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

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

    /**
     * @param ChangePasswordAckDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ChangePasswordAckDto) {
            throw new InvalidArgumentException('data must be ChangePasswordAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
