<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Common\Http;

use InterfaceApi\Support\BaseResponse;

/**
 * 非分页列表 API 响应基类。{@see BaseResponse::$data} 应为 {@see \InterfaceApi\ScheduleJob\App\Module\Common\Dto\AbstractListDataDto} 子类（total + list）。
 */
class BaseListResponse extends BaseResponse
{
}
