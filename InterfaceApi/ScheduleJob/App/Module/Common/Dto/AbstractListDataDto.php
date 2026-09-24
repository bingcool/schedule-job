<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Common\Dto;

use InterfaceApi\Support\ApiProperty;

/**
 * 列表 data 基类：{@see \InterfaceApi\Support\AbstractListDataDto} 提供 total/list 存储；
 * 子类用 #[ArrayList] 声明元素类型并实现 typed getList/setList。
 */
abstract class AbstractListDataDto extends \InterfaceApi\Support\AbstractListDataDto
{
    #[ApiProperty(description: '总条数')]
    protected int $total = 0;
}
