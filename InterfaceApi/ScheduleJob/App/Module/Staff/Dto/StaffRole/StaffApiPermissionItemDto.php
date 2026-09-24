<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Dto\StaffRole;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class StaffApiPermissionItemDto extends AbstractDto
{
    #[ApiProperty(description: '权限 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '名称')]
    protected string $name = '';

    #[ApiProperty(description: 'HTTP 方法')]
    protected string $method = '';

    #[ApiProperty(description: '路径')]
    protected string $path = '';

    #[ApiProperty(description: '分组')]
    protected string $group = '';
}
