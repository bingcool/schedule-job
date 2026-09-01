<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class GrantUserRolesDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '角色 ID')]
    protected array $roleIds = [];

    /**
     * @param array<int, int> $roleIds
     */
    public static function of(int $id, array $roleIds): self
    {
        $dto = new self();
        $dto->id = $id;
        $dto->roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array<int, int>
     */
    public function getRoleIds(): array
    {
        return $this->roleIds;
    }
}
