<?php

declare(strict_types=1);

namespace App\Module\Common\Dto;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class DeleteAckDto extends AbstractDto
{
    #[ApiProperty(description: '记录 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '是否已删除')]
    protected bool $deleted = true;

    public static function of(int $id, bool $deleted = true): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->deleted = $deleted;

        return $dto;
    }
}
