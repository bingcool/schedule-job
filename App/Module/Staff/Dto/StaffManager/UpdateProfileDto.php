<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class UpdateProfileDto extends AbstractDto
{
    #[ApiProperty(description: '用户名称')]
    protected string $userName = '';

    public static function of(string $userName): self
    {
        $dto = new self();
        $dto->userName = $userName;

        return $dto;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }
}
