<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffUserRolesRequest extends BaseRequest
{
    #[ApiProperty(description: '用户 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '角色 ID 列表')]
    protected array $roleIds = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return array<int, int>
     */
    public function getRoleIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->roleIds)));
    }

    /**
     * @param array<int, int> $roleIds
     */
    public function setRoleIds(array $roleIds): static
    {
        $this->roleIds = $roleIds;

        return $this;
    }
}
