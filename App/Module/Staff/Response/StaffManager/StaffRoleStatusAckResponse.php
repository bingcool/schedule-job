<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffRoleStatusAckResponse extends BaseResponse
{
    #[ApiProperty(description: '角色 ID')]
    protected int $id;

    #[ApiProperty(description: '状态：1=启用，0=禁用')]
    protected int $status;

    public function __construct(int $id, int $status)
    {
        $this->id = $id;
        $this->status = $status;
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}
