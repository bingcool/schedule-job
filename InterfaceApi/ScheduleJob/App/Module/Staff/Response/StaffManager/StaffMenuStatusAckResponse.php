<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\StatusSwitchAckDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class StaffMenuStatusAckResponse extends BaseResponse
{
    #[ApiProperty(description: '菜单状态确认 data')]
    protected StatusSwitchAckDto $data;

    public function __construct(int $id, int $status)
    {
        $this->data = StatusSwitchAckDto::of($id, $status);
    }

    public function getData(): StatusSwitchAckDto
    {
        return $this->data;
    }

    /**
     * @param StatusSwitchAckDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof StatusSwitchAckDto) {
            throw new InvalidArgumentException('data must be StatusSwitchAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
