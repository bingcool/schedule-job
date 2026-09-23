<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffRole;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class StaffMenuSortAckDto extends AbstractDto
{
    #[ApiProperty(description: '父菜单 ID')]
    protected int $parentId = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '已保存的菜单 ID 顺序')]
    protected array $ids = [];

    /**
     * @param array<int, int> $ids
     */
    public static function of(int $parentId, array $ids): self
    {
        $dto = new self();
        $dto->parentId = $parentId;
        $dto->ids = array_values($ids);

        return $dto;
    }
}
