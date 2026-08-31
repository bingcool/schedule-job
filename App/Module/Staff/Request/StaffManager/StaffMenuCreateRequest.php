<?php

declare(strict_types=1);

namespace App\Module\Staff\Request\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class StaffMenuCreateRequest extends BaseRequest
{
    #[ApiProperty(description: '菜单名称')]
    #[ValidationRule(rule: 'required|string', message: 'name 不能为空')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    #[ValidationRule(rule: 'required|string', message: 'code 不能为空')]
    protected string $code = '';

    #[ApiProperty(description: '页面 URI')]
    #[ValidationRule(rule: 'required|string', message: 'uri 不能为空')]
    protected string $uri = '';

    #[ApiProperty(description: '图标 class')]
    protected string $icon = '';

    #[ApiProperty(description: '父菜单 ID，0 为顶级')]
    #[StringToInt]
    protected int $parentId = 0;

    #[ApiProperty(description: '排序，越大越靠前')]
    #[StringToInt]
    protected int $sort = 0;

    #[ApiProperty(description: '状态：1=启用，0=禁用')]
    #[StringToInt]
    protected int $status = 1;

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

    public function getUri(): string
    {
        return trim($this->uri);
    }

    public function setUri(string $uri): static
    {
        $this->uri = $uri;

        return $this;
    }

    public function getIcon(): string
    {
        return trim($this->icon);
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getParentId(): int
    {
        return max(0, $this->parentId);
    }

    public function setParentId(int $parentId): static
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getSort(): int
    {
        return max(0, $this->sort);
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;

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
}
