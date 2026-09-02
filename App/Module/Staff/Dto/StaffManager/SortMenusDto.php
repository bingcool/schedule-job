<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class SortMenusDto extends AbstractDto
{
    #[ApiProperty(description: '父菜单 ID，0 表示顶级分组')]
    protected int $parentId = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '同级菜单 ID，按展示顺序排列')]
    protected array $ids = [];

    /**
     * @param array<int, int> $ids
     */
    public static function of(int $parentId, array $ids): self
    {
        $dto = new self();
        $dto->parentId = max(0, $parentId);
        $dto->ids = array_values(array_unique(array_map('intval', $ids)));

        return $dto;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @return array<int, int>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
