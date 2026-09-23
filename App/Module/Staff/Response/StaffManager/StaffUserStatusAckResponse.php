<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Common\Dto\StatusSwitchAckDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffUserStatusAckResponse extends BaseResponse
{
    #[ApiProperty(description: '用户状态确认 data')]
    protected StatusSwitchAckDto $data;

    public function __construct(int $id, int $status)
    {
        $this->data = StatusSwitchAckDto::of($id, $status);
    }

    public function getData(): StatusSwitchAckDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof StatusSwitchAckDto) {
            throw new InvalidArgumentException('data must be StatusSwitchAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
