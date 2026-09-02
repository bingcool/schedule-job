<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffMenuSortRequest extends BaseRequest
{
    #[ApiProperty(description: '父菜单 ID，0 表示顶级分组')]
    #[ValidationRule(rule: 'required|int', message: 'parentId 不能为空')]
    #[StringToInt]
    protected int $parentId = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '同级菜单 ID，按展示顺序排列')]
    #[ValidationRule(rule: 'required|array', message: 'ids 不能为空')]
    protected array $ids = [];

    public function getParentId(): int
    {
        return max(0, $this->parentId);
    }

    public function setParentId(int $parentId): static
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * @return array<int, int>
     */
    public function getIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->ids)));
    }

    /**
     * @param array<int, int> $ids
     */
    public function setIds(array $ids): static
    {
        $this->ids = $ids;

        return $this;
    }
}
