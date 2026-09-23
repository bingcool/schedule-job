<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffUser;

use Swoolefy\Annotation\ApiProperty;

class UpdateUserDto extends CreateUserDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }
}
