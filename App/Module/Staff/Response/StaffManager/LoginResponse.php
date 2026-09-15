<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffManager\AuthSessionDto;
use Swoolefy\Http\BaseResponse;

/**
 * 登录响应。loginMode / tempPasswordExpiresAt 给前端按 user_id 缓存登录方式。
 */
class LoginResponse extends BaseResponse
{
    protected AuthSessionDto $session;

    public function __construct(AuthSessionDto $session)
    {
        $this->session = $session;
    }

    public function getData(): array
    {
        return [
            'token' => $this->session->getToken(),
            'tokenType' => $this->session->getTokenType(),
            'expiresIn' => $this->session->getExpiresIn(),
            'user' => $this->session->getUser(),
            'loginMode' => $this->session->getLoginMode(),
            'tempPasswordExpiresAt' => $this->session->getTempPasswordExpiresAt(),
        ];
    }
}
