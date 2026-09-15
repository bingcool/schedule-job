<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffManager\ResetPasswordAckDto;
use Swoolefy\Http\BaseResponse;

/** 确认重置响应。mailSent=false 时前端提示邮件失败或未绑定邮箱。 */
class ResetPasswordAckResponse extends BaseResponse
{
    protected ResetPasswordAckDto $dto;

    public function __construct(ResetPasswordAckDto $dto)
    {
        $this->dto = $dto;
    }

    public function getData(): array
    {
        return [
            'id' => $this->dto->getId(),
            'changed' => true,
            'mailSent' => $this->dto->getMailSent(),
            'email' => $this->dto->getEmail(),
        ];
    }
}
