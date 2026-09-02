<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffMenuSortAckResponse extends BaseResponse
{
    #[ApiProperty(description: '父菜单 ID')]
    protected int $parentId;

    /**
     * @var array<int, int>
     */
    #[ApiProperty(description: '已保存的菜单 ID 顺序')]
    protected array $ids;

    /**
     * @param array<int, int> $ids
     */
    public function __construct(int $parentId, array $ids)
    {
        $this->parentId = $parentId;
        $this->ids = $ids;
    }

    public function getData(): array
    {
        return [
            'parentId' => $this->parentId,
            'ids' => $this->ids,
        ];
    }
}
