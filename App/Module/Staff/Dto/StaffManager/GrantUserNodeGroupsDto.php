<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class GrantUserNodeGroupsDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '授权节点组 ID')]
    protected array $nodeGroupIds = [];

    /**
     * @param array<int, int> $nodeGroupIds
     */
    public static function of(int $id, array $nodeGroupIds): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->nodeGroupIds = array_values(array_unique(array_map('intval', $nodeGroupIds)));

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array<int, int>
     */
    public function getNodeGroupIds(): array
    {
        return $this->nodeGroupIds;
    }
}
