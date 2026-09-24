<?php

declare(strict_types=1);

namespace InterfaceApi\Support;

/**
 * SDK copy: typed array collections (ArrayInteger, ArrayString, …).
 */
interface ArrayInterface
{
    public function toArray(): array;

    public function toDeepArray(): array;
}
