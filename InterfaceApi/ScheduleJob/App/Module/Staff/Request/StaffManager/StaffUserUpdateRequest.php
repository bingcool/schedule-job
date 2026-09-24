<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class StaffUserUpdateRequest extends BaseRequest
{
    #[ApiProperty(description: '用户 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    #[ApiProperty(description: '账号（建议邮箱）')]
    #[ValidationRule(rule: 'required|string', message: 'account 不能为空')]
    protected string $account = '';

    #[ApiProperty(description: '邮箱，可选；账号为邮箱时以后端同步值为准')]
    protected ?string $email = null;

    #[ApiProperty(description: '用户名称')]
    #[ValidationRule(rule: 'required|string', message: 'userName 不能为空')]
    protected string $userName = '';

    #[ApiProperty(description: '新密码，空则不改')]
    protected string $password = '';

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '角色 ID 列表')]
    protected array $roleIds = [];

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

    public function getAccount(): string
    {
        return trim($this->account);
    }

    public function setAccount(string $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getEmail(): string
    {
        return trim((string) $this->email);
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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
