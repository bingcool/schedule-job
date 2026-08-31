<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class MenuIdDto extends AbstractDto
{
    #[ApiProperty(description: '菜单 ID')]
    protected int $id = 0;

    public static function of(int $id): self
    {
        $dto = new self();
        $dto->id = $id;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
