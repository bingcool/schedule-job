<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Common\Dto\StatusSwitchAckDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffRoleStatusAckResponse extends BaseResponse
{
    #[ApiProperty(description: '角色状态确认 data')]
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
