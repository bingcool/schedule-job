<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Common\Enum;

use InterfaceApi\Support\Enum\BaseIntEnum;
use InterfaceApi\Support\Enum\Concerns\InteractsWithBackedEnumLabel;

enum PersonSexEnum: int implements BaseIntEnum
{
    use InteractsWithBackedEnumLabel;

    case UNKNOWN = 0;
    case MALE = 1;
    case FEMALE = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::UNKNOWN => '未知',
            self::MALE => '男',
            self::FEMALE => '女',
        };
    }
}
