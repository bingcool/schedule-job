<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class StaffRoleRowDto extends AbstractDto
{
    #[ApiProperty(description: '角色 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '角色名称')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '描述')]
    protected string $desc = '';

    #[ApiProperty(description: '是否超管')]
    protected bool $isSuperRole = false;

    #[ApiProperty(description: '状态')]
    protected int $status = 1;

    #[ApiProperty(description: '关联用户数')]
    protected int $userCount = 0;

    #[ApiProperty(description: '菜单权限数')]
    protected int $menuCount = 0;

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

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    #[ApiProperty(description: '更新时间')]
    protected string $updatedAt = '';

    /**
     * @param array<string, mixed> $row
     */
    public static function fromEntityRow(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->name = (string) ($row['name'] ?? '');
        $dto->code = (string) ($row['code'] ?? '');
        $dto->desc = (string) ($row['desc'] ?? '');
        $dto->isSuperRole = (int) ($row['is_super_role'] ?? $row['isSuperRole'] ?? 0) === 1;
        $dto->status = (int) ($row['status'] ?? 1);
        $dto->userCount = (int) ($row['user_count'] ?? $row['userCount'] ?? 0);
        $dto->menuCount = (int) ($row['menu_count'] ?? $row['menuCount'] ?? 0);
        $dto->pageIds = array_values(array_map('intval', $row['page_ids'] ?? $row['pageIds'] ?? []));
        $dto->apiPerIds = array_values(array_map('intval', $row['api_per_ids'] ?? $row['apiPerIds'] ?? []));
        $dto->taskPerIds = array_values(array_map('intval', $row['task_per_ids'] ?? $row['taskPerIds'] ?? []));
        $dto->createdAt = (string) ($row['created_at'] ?? $row['createdAt'] ?? '');
        $dto->updatedAt = (string) ($row['updated_at'] ?? $row['updatedAt'] ?? '');

        return $dto;
    }
}
