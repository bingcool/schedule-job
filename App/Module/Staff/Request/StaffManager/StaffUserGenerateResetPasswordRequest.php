<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

/**
 * POST /users/generate-reset-password。前端只传 userId。
 */
class StaffUserGenerateResetPasswordRequest extends BaseRequest
{
    #[ApiProperty(description: '目标用户 ID')]
    #[ValidationRule(rule: 'required|integer|min:1', message: 'userId 无效')]
    #[StringToInt]
    protected int $userId = 0;

    public function getUserId(): int
    {
        return (int) $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }
}
