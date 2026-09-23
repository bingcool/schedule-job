<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Staff\Dto\StaffRole\RoleOptionsListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class RoleOptionsResponse extends BaseListResponse
{
    #[ApiProperty(description: '角色选项 data')]
    protected RoleOptionsListDataDto $data;

    /**
     * @param RoleOptionsListDataDto|list<\App\Module\Staff\Dto\StaffRole\StaffRoleOptionDto> $list
     */
    public function __construct(RoleOptionsListDataDto|array $list)
    {
        $this->data = $list instanceof RoleOptionsListDataDto
            ? $list
            : RoleOptionsListDataDto::fromItems($list);
    }

    public function getData(): RoleOptionsListDataDto
    {
        return $this->data;
    }

    /**
     * @param RoleOptionsListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof RoleOptionsListDataDto) {
            throw new InvalidArgumentException('data must be RoleOptionsListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
