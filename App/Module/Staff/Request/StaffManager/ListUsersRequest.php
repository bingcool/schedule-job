<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BasePageRequest;

class ListUsersRequest extends BasePageRequest
{
    #[ApiProperty(description: '账号关键词')]
    #[ValidationRule(rule: 'nullable|string', message: 'account 格式错误')]
    protected ?string $account = null;

    #[ApiProperty(description: '用户名称关键词')]
    #[ValidationRule(rule: 'nullable|string', message: 'userName 格式错误')]
    protected ?string $userName = null;

    #[ApiProperty(description: '状态：1=正常，0=已禁用')]
    #[ValidationRule(rule: 'nullable|int', message: 'status 必须是整数')]
    #[StringToInt]
    protected ?int $status = null;

    public function getAccount(): ?string
    {
        return $this->account;
    }

    public function setAccount(?string $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }
}
