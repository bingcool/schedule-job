<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffRolePagesRequest extends BaseRequest
{
    #[ApiProperty(description: '角色 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '菜单页面 ID 列表')]
    protected array $pageIds = [];

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
    public function getPageIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->pageIds)));
    }

    /**
     * @param array<int, int> $pageIds
     */
    public function setPageIds(array $pageIds): static
    {
        $this->pageIds = $pageIds;

        return $this;
    }
}
