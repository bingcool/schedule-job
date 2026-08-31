<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class ListUsersQueryDto extends AbstractDto
{
    #[ApiProperty(description: '页码')]
    protected int $page = 1;

    #[ApiProperty(description: '每页条数')]
    protected int $pageSize = 20;

    #[ApiProperty(description: '账号关键词')]
    protected ?string $account = null;

    #[ApiProperty(description: '用户名称关键词')]
    protected ?string $userName = null;

    #[ApiProperty(description: '状态：1=正常，0=禁用')]
    protected ?int $status = null;

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): static
    {
        $this->page = max(1, $page);

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): static
    {
        $this->pageSize = max(1, $pageSize);

        return $this;
    }

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

    public function getOffset(): int
    {
        return ($this->getPage() - 1) * $this->getPageSize();
    }
}
