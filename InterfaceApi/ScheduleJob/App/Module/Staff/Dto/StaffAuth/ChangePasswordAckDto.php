<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffAuth;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class ChangePasswordAckDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '密码是否已修改')]
    protected bool $changed = true;

    public static function of(int $id, bool $changed = true): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->changed = $changed;

        return $dto;
    }
}
