<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    #[ApiProperty(description: '用户名称')]
    #[ValidationRule(rule: 'required|string', message: '用户名称不能为空')]
    protected string $userName = '';

    public function getUserName(): string
    {
        return trim($this->userName);
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }
}
