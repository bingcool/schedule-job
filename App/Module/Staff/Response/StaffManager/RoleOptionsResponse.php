<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffRole\StaffRoleOptionDto;
use App\Module\Staff\Dto\StaffUser\StaffUserBriefDto;
use Swoolefy\Core\Dto\AbstractDto;
use Swoolefy\Http\BaseResponse;

class RoleOptionsResponse extends BaseResponse
{
    /**
     * @var list<StaffRoleOptionDto|StaffUserBriefDto|AbstractDto>
     */
    protected array $list;

    /**
     * @param list<StaffRoleOptionDto|StaffUserBriefDto|array<string, mixed>> $list
     */
    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function getData(): array
    {
        $rows = [];
        foreach ($this->list as $item) {
            $rows[] = $item instanceof AbstractDto ? $item->toDeepArray() : $item;
        }

        return [
            'list' => $rows,
            'total' => count($rows),
        ];
    }
}
