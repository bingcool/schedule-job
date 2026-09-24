<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffAuth\AuthMeProfileDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class AuthMeResponse extends BaseResponse
{
    #[ApiProperty(description: '当前用户 data')]
    protected AuthMeProfileDto $data;

    public function __construct(AuthMeProfileDto $user)
    {
        $this->data = $user;
    }

    public function getData(): AuthMeProfileDto
    {
        return $this->data;
    }

    /**
     * @param AuthMeProfileDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof AuthMeProfileDto) {
            throw new InvalidArgumentException('data must be AuthMeProfileDto');
        }
        $this->data = $data;

        return $this;
    }
}
