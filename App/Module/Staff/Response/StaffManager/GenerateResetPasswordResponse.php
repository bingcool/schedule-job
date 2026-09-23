<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffUser\GeneratedResetPasswordDto;
use Swoolefy\Http\BaseResponse;

/** 生成密码接口响应，password 回填到前端只读输入框。 */
class GenerateResetPasswordResponse extends BaseResponse
{
    protected GeneratedResetPasswordDto $dto;

    public function __construct(GeneratedResetPasswordDto $dto)
    {
        $this->dto = $dto;
    }

    public function getData(): array
    {
        return [
            'userId' => $this->dto->getUserId(),
            'password' => $this->dto->getPassword(),
            'expiresAt' => $this->dto->getExpiresAt(),
            'email' => $this->dto->getEmail(),
        ];
    }
}
