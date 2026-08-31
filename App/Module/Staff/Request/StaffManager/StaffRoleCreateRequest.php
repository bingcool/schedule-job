<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffRoleCreateRequest extends BaseRequest
{
    #[ApiProperty(description: '角色名称')]
    #[ValidationRule(rule: 'required|string', message: 'name 不能为空')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    #[ValidationRule(rule: 'required|string', message: 'code 不能为空')]
    protected string $code = '';

    #[ApiProperty(description: '角色描述')]
    protected string $desc = '';

    #[ApiProperty(description: '状态：1=启用，0=禁用')]
    #[StringToInt]
    protected int $status = 1;

    #[ApiProperty(description: '是否超级管理员：1=是，0=否')]
    #[StringToInt]
    protected int $isSuperRole = 0;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '菜单页面 ID 列表')]
    protected array $pageIds = [];

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: 'API 权限 ID 列表（type=1）')]
    protected array $apiPerIds = [];

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '任务接口权限 ID 列表（type=2）')]
    protected array $taskPerIds = [];

    public function getName(): string
    {
        return trim($this->name);
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return trim($this->code);
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getDesc(): string
    {
        return trim($this->desc);
    }

    public function setDesc(string $desc): static
    {
        $this->desc = $desc;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status === 0 ? 0 : 1;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIsSuperRole(): int
    {
        return $this->isSuperRole === 1 ? 1 : 0;
    }

    public function setIsSuperRole(int $isSuperRole): static
    {
        $this->isSuperRole = $isSuperRole;

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

    /**
     * @return array<int, int>
     */
    public function getApiPerIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->apiPerIds)));
    }

    /**
     * @param array<int, int> $apiPerIds
     */
    public function setApiPerIds(array $apiPerIds): static
    {
        $this->apiPerIds = $apiPerIds;

        return $this;
    }

    /**
     * @return array<int, int>
     */
    public function getTaskPerIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->taskPerIds)));
    }

    /**
     * @param array<int, int> $taskPerIds
     */
    public function setTaskPerIds(array $taskPerIds): static
    {
        $this->taskPerIds = $taskPerIds;

        return $this;
    }
}
