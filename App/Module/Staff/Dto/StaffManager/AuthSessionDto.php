<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

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

    /**
     * @param array<string, mixed> $user
     */
    public static function of(string $token, int $expiresIn, array $user): self
    {
        $dto = new self();
        $dto->token = $token;
        $dto->expiresIn = $expiresIn;
        $dto->user = $user;

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
}
