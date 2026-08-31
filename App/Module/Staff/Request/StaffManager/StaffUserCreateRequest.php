<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffUserCreateRequest extends BaseRequest
{
    #[ApiProperty(description: '账号（建议邮箱）')]
    #[ValidationRule(rule: 'required|string', message: 'account 不能为空')]
    protected string $account = '';

    #[ApiProperty(description: '用户名称')]
    #[ValidationRule(rule: 'required|string', message: 'userName 不能为空')]
    protected string $userName = '';

    #[ApiProperty(description: '密码')]
    #[ValidationRule(rule: 'required|string', message: 'password 不能为空')]
    protected string $password = '';

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '角色 ID 列表')]
    protected array $roleIds = [];

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '授权节点组 ID 列表，空表示不限制')]
    protected array $nodeGroupIds = [];

    public function getAccount(): string
    {
        return trim($this->account);
    }

    public function setAccount(string $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getUserName(): string
    {
        return trim($this->userName);
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

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
