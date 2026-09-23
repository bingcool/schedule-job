<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffAuth\AuthSessionDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

/**
 * 登录响应。loginMode / tempPasswordExpiresAt 给前端按 user_id 缓存登录方式。
 */
class LoginResponse extends BaseResponse
{
    #[ApiProperty(description: '登录会话 data')]
    protected AuthSessionDto $data;

    public function __construct(AuthSessionDto $session)
    {
        $this->data = $session;
    }

    public function getData(): AuthSessionDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof AuthSessionDto) {
            throw new InvalidArgumentException('data must be AuthSessionDto');
        }
        $this->data = $data;

        return $this;
    }
}
