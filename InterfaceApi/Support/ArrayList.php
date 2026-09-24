<?php

declare(strict_types=1);

namespace InterfaceApi\Support;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ArrayList
{
    public function __construct(
        protected string $itemClass = ''
    ) {
    }

    public function getItemClass(): string
    {
        return $this->itemClass;
    }
}
