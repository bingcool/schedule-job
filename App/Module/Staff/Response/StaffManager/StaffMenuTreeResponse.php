<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Staff\Dto\StaffRole\StaffMenuTreeListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class StaffMenuTreeResponse extends BaseListResponse
{
    #[ApiProperty(description: '菜单树 data')]
    protected StaffMenuTreeListDataDto $data;

    /**
     * @param StaffMenuTreeListDataDto|list<\App\Module\Staff\Dto\StaffRole\StaffMenuRowDto|array<string, mixed>> $list
     */
    public function __construct(StaffMenuTreeListDataDto|array $list)
    {
        $this->data = $list instanceof StaffMenuTreeListDataDto
            ? $list
            : StaffMenuTreeListDataDto::fromItems($list);
    }

    public function getData(): StaffMenuTreeListDataDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof StaffMenuTreeListDataDto) {
            throw new InvalidArgumentException('data must be StaffMenuTreeListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
