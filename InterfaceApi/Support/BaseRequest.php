<?php

declare(strict_types=1);

namespace InterfaceApi\Support;

/**
 * 契约入参基类。schedule-job 内继承 Swoolefy {@see \Swoolefy\Http\BaseRequest}，与 HttpRoute / RequestValidate 兼容。
 */
class BaseRequest extends \Swoolefy\Http\BaseRequest
{
}
