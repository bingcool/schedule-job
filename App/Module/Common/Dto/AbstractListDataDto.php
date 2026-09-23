<?php

declare(strict_types=1);

namespace App\Module\Common\Dto;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

abstract class AbstractListDataDto extends AbstractDto
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
