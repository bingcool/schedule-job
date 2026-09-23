<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffUser\ResetPasswordAckDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

/** 确认重置响应。mailSent=false 时前端提示邮件失败或未绑定邮箱。 */
class ResetPasswordAckResponse extends BaseResponse
{
    #[ApiProperty(description: '重置密码确认 data')]
    protected ResetPasswordAckDto $data;

    public function __construct(ResetPasswordAckDto $data)
    {
        $this->data = $data;
    }

    public function getData(): ResetPasswordAckDto
    {
        return $this->data;
    }

    /**
     * @param ResetPasswordAckDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof ResetPasswordAckDto) {
            throw new InvalidArgumentException('data must be ResetPasswordAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
