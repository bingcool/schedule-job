<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffAuth;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

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
