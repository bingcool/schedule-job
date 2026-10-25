<?php

declare(strict_types=1);

namespace InterfaceApi\Support;

use Attribute;

/**
 * 契约层校验注解。#[Attribute] 不会被继承，子类必须自己再声明一次，
 * 否则 ReflectionAttribute::newInstance() 会抛「non-attribute class」。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ValidationRule extends \Swoolefy\Annotation\Validation\ValidationRule
{
}
