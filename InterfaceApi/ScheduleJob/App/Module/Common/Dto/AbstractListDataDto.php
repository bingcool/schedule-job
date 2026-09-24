<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Common\Dto;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

abstract class AbstractListDataDto extends \InterfaceApi\Support\AbstractListDataDto
{
    #[ApiProperty(description: '总条数')]
    protected int $total = 0;

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array<int, object>
     */
    abstract public function getList(): array;
}
