<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffRole;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class StaffTaskPermissionItemDto extends AbstractDto
{
    #[ApiProperty(description: '权限 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '名称')]
    protected string $name = '';

    #[ApiProperty(description: '唯一标识')]
    protected string $code = '';

    #[ApiProperty(description: '描述')]
    protected string $desc = '';

    /**
     * @return list<self>
     */
    public static function catalog(): array
    {
        $rows = [
            ['id' => 1, 'name' => '立即执行', 'code' => 'cron:task:run_once', 'desc' => '手动触发任务执行'],
            ['id' => 2, 'name' => '启用/禁用', 'code' => 'cron:task:switch', 'desc' => '切换任务启用状态'],
            ['id' => 3, 'name' => '查看日志', 'code' => 'cron:task:logs', 'desc' => '查看任务执行日志'],
            ['id' => 4, 'name' => '编辑 GLUE', 'code' => 'cron:task:glue_edit', 'desc' => '编辑 GLUE 脚本内容'],
        ];

        return array_map(static fn (array $row): self => self::fromSlice($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromSlice(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->name = (string) ($row['name'] ?? '');
        $dto->code = (string) ($row['code'] ?? '');
        $dto->desc = (string) ($row['desc'] ?? '');

        return $dto;
    }
}
