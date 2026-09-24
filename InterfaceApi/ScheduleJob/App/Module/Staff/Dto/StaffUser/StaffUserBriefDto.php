<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffUser;

use InterfaceApi\ScheduleJob\App\Module\Staff\Entity\StaffUserEntity;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffUserBriefDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '账号')]
    protected string $account = '';

    #[ApiProperty(description: '用户名称')]
    protected string $userName = '';

    public static function fromUserEntity(StaffUserEntity $user): self
    {
        $dto = new self();
        $dto->id = (int) $user->id;
        $dto->account = (string) $user->account;
        $dto->userName = (string) $user->user_name;

        return $dto;
    }
}
