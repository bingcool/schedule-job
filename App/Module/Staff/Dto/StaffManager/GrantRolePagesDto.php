<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class GrantRolePagesDto extends AbstractDto
{
    #[ApiProperty(description: '角色 ID')]
    protected int $id = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '菜单页面 ID 列表')]
    protected array $pageIds = [];

    /**
     * @param array<int, int> $pageIds
     */
    public static function of(int $id, array $pageIds): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->pageIds = array_values(array_unique(array_map('intval', $pageIds)));

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array<int, int>
     */
    public function getPageIds(): array
    {
        return $this->pageIds;
    }
}
