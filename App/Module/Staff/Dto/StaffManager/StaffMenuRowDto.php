<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class StaffMenuRowDto extends AbstractDto
{
    #[ApiProperty(description: '菜单 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '应用 ID')]
    protected int $appId = 0;

    #[ApiProperty(description: '菜单名称')]
    protected string $name = '';

    #[ApiProperty(description: '父路径')]
    protected string $parentPrefix = '';

    #[ApiProperty(description: '父菜单 ID')]
    protected int $parentId = 0;

    #[ApiProperty(description: 'URI')]
    protected string $uri = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '图标')]
    protected string $icon = '';

    #[ApiProperty(description: '排序')]
    protected int $sort = 0;

    #[ApiProperty(description: '状态')]
    protected int $status = 1;

    /**
     * @var array<int, StaffMenuRowDto>
     */
    #[ApiProperty(description: '子菜单')]
    protected array $children = [];

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    /**
     * @param array<string, mixed> $row
     */
    public static function fromEntityRow(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->appId = (int) ($row['app_id'] ?? $row['appId'] ?? 0);
        $dto->name = (string) ($row['name'] ?? '');
        $dto->parentPrefix = (string) ($row['parent_prefix'] ?? $row['parentPrefix'] ?? '');
        $dto->parentId = (int) ($row['parent_id'] ?? $row['parentId'] ?? 0);
        $dto->uri = (string) ($row['uri'] ?? '');
        $dto->code = (string) ($row['code'] ?? '');
        $dto->icon = (string) ($row['icon'] ?? '');
        $dto->sort = (int) ($row['sort'] ?? 0);
        $dto->status = (int) ($row['status'] ?? 1);
        $dto->createdAt = (string) ($row['created_at'] ?? $row['createdAt'] ?? '');

        return $dto;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addChild(self $child): void
    {
        $this->children[] = $child;
    }

    /**
     * @return array<int, StaffMenuRowDto>
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
