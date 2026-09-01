<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffUserNodeGroupsRequest extends BaseRequest
{
    #[ApiProperty(description: '用户 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '授权节点组 ID 列表')]
    protected array $nodeGroupIds = [];

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
    public function getNodeGroupIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->nodeGroupIds)));
    }

    /**
     * @param array<int, int> $nodeGroupIds
     */
    public function setNodeGroupIds(array $nodeGroupIds): static
    {
        $this->nodeGroupIds = $nodeGroupIds;

        return $this;
    }
}
