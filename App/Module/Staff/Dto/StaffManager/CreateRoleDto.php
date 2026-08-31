<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class CreateRoleDto extends AbstractDto
{
    #[ApiProperty(description: '角色名称')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '描述')]
    protected string $desc = '';

    #[ApiProperty(description: '状态')]
    protected int $status = 1;

    #[ApiProperty(description: '是否超管')]
    protected int $isSuperRole = 0;

    /**
     * @var array<int, int>
     */
    protected array $pageIds = [];

    /**
     * @var array<int, int>
     */
    protected array $apiPerIds = [];

    /**
     * @var array<int, int>
     */
    protected array $taskPerIds = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getDesc(): string
    {
        return $this->desc;
    }

    public function setDesc(string $desc): static
    {
        $this->desc = $desc;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIsSuperRole(): int
    {
        return $this->isSuperRole;
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
        return $this->pageIds;
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
        return $this->apiPerIds;
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
        return $this->taskPerIds;
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
