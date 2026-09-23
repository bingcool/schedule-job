<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffAuth;

use App\Module\Staff\Dto\StaffRole\StaffMenuRowDto;
use App\Module\Staff\Dto\StaffRole\StaffRoleBriefDto;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class AuthMeProfileDto extends AbstractDto
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '账号')]
    protected string $account = '';

    #[ApiProperty(description: '邮箱')]
    protected string $email = '';

    #[ApiProperty(description: '用户名称')]
    protected string $userName = '';

    #[ApiProperty(description: '是否超级管理员')]
    protected bool $isSuper = false;

    #[ApiProperty(description: '是否任务组编辑角色')]
    protected bool $isEditorTaskGroup = false;

    /**
     * @var array<int, StaffRoleBriefDto>
     */
    #[ApiProperty(description: '角色列表')]
    protected array $roles = [];

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '授权节点组 ID')]
    protected array $nodeGroupIds = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: '菜单树')]
    protected array $menus = [];

    /**
     * @param list<StaffRoleBriefDto> $roles
     * @param list<StaffMenuRowDto> $menuRoots
     */
    public static function of(
        int $id,
        string $account,
        string $email,
        string $userName,
        bool $isSuper,
        bool $isEditorTaskGroup,
        array $roles,
        array $nodeGroupIds,
        array $menuRoots,
    ): self {
        $dto = new self();
        $dto->id = $id;
        $dto->account = $account;
        $dto->email = $email;
        $dto->userName = $userName;
        $dto->isSuper = $isSuper;
        $dto->isEditorTaskGroup = $isEditorTaskGroup;
        $dto->roles = $roles;
        $dto->nodeGroupIds = array_values(array_map('intval', $nodeGroupIds));
        $dto->menus = self::menuRootsToArray($menuRoots);

        return $dto;
    }

    /**
     * @param list<StaffMenuRowDto> $roots
     * @return array<int, array<string, mixed>>
     */
    public static function menuRootsToArray(array $roots): array
    {
        $out = [];
        foreach ($roots as $root) {
            $out[] = $root->toDeepArray();
        }

        return $out;
    }

    /**
     * @return list<StaffRoleBriefDto>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMenus(): array
    {
        return $this->menus;
    }

    public function getIsSuper(): bool
    {
        return $this->isSuper;
    }

    public function toDeepArray(): array
    {
        $data = parent::toDeepArray();
        $data['roles'] = array_map(
            static fn (StaffRoleBriefDto $role): array => $role->toDeepArray(),
            $this->roles,
        );

        return $data;
    }
}
