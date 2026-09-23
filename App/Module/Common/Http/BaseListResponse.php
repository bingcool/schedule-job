<?php

declare(strict_types=1);

namespace App\Module\Common\Http;

use Swoolefy\Http\BaseResponse;

/**
 * 非分页列表 API 响应基类。{@see BaseResponse::$data} 应为 {@see \App\Module\Common\Dto\AbstractListDataDto} 子类（total + list）。
 */
class BaseListResponse extends BaseResponse
{
}
