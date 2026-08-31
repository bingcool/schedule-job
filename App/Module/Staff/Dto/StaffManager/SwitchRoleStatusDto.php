<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class SwitchRoleStatusDto extends AbstractDto
{
    #[ApiProperty(description: '角色 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '1=启用，0=禁用')]
    protected int $status = 1;

    public static function of(int $id, int $status): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->status = $status === 0 ? 0 : 1;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
