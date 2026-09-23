<?php

declare(strict_types=1);

namespace App\Module\Common\Dto;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class StatusSwitchAckDto extends AbstractDto
{
    #[ApiProperty(description: '记录 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '更新后的状态')]
    protected int $status = 0;

    public static function of(int $id, int $status): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->status = $status;

        return $dto;
    }
}
