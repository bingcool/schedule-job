<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Common\Dto;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class DeleteAckDto extends AbstractDto
{
    #[ApiProperty(description: '记录 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '是否已删除')]
    protected bool $deleted = true;

    public static function of(int $id, bool $deleted = true): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->deleted = $deleted;

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }
}
