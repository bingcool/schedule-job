<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager;

use InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser\GeneratedResetPasswordDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

/** 生成密码接口响应，password 回填到前端只读输入框。 */
class GenerateResetPasswordResponse extends BaseResponse
{
    #[ApiProperty(description: '生成重置密码 data')]
    protected GeneratedResetPasswordDto $data;

    public function __construct(GeneratedResetPasswordDto $data)
    {
        $this->data = $data;
    }

    public function getData(): GeneratedResetPasswordDto
    {
        return $this->data;
    }

    /**
     * @param GeneratedResetPasswordDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof GeneratedResetPasswordDto) {
            throw new InvalidArgumentException('data must be GeneratedResetPasswordDto');
        }
        $this->data = $data;

        return $this;
    }
}
