<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

/**
 * 登录成功会话。
 * loginMode=temp 时 tempPasswordExpiresAt 有值，前端按 user_id 缓存后展示顶部 ❗。
 */
class AuthSessionDto extends AbstractDto
{
    #[ApiProperty(description: 'JWT')]
    protected string $token = '';

    #[ApiProperty(description: 'token 类型')]
    protected string $tokenType = 'Bearer';

    #[ApiProperty(description: '过期秒数')]
    protected int $expiresIn = 3600;

    /**
     * @var array<string, mixed>
     */
    #[ApiProperty(description: '当前用户')]
    protected array $user = [];

    #[ApiProperty(description: '登录方式：temp=临时重置密码，normal=正常密码')]
    protected string $loginMode = 'normal';

    #[ApiProperty(description: '临时重置密码到期时间，正常登录为空')]
    protected string $tempPasswordExpiresAt = '';

    /**
     * @param array<string, mixed> $user
     */
    public static function of(
        string $token,
        int $expiresIn,
        array $user,
        string $loginMode = 'normal',
        string $tempPasswordExpiresAt = '',
    ): self {
        $dto = new self();
        $dto->token = $token;
        $dto->expiresIn = $expiresIn;
        $dto->user = $user;
        $dto->loginMode = $loginMode === 'temp' ? 'temp' : 'normal';
        $dto->tempPasswordExpiresAt = $tempPasswordExpiresAt;

        return $dto;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUser(): array
    {
        return $this->user;
    }

    public function getLoginMode(): string
    {
        return $this->loginMode;
    }

    public function getTempPasswordExpiresAt(): string
    {
        return $this->tempPasswordExpiresAt;
    }
}
