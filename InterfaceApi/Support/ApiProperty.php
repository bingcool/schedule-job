<?php

declare(strict_types=1);

namespace InterfaceApi\Support;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ApiProperty
{
    public function __construct(
        protected string $description = ''
    ) {
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
