<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffUserByNodeGroupRequest extends BaseRequest
{
    #[ApiProperty(description: '节点分组 ID')]
    #[ValidationRule(rule: 'required|int', message: 'nodeGroupId 不能为空')]
    #[StringToInt]
    protected int $nodeGroupId = 0;

    public function getNodeGroupId(): int
    {
        return $this->nodeGroupId;
    }

    public function setNodeGroupId(int $nodeGroupId): static
    {
        $this->nodeGroupId = $nodeGroupId;

        return $this;
    }
}
