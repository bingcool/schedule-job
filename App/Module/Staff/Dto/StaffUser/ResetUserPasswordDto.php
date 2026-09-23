<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffUser;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

/**
 * 确认重置入参：只需目标用户 id 和已生成的那一条 32 位密码。
 */
class ResetUserPasswordDto extends AbstractDto
{
    #[ApiProperty(description: '目标用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '已生成的临时重置密码')]
    protected string $password = '';

    public static function of(int $id, string $password): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->password = $password;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
