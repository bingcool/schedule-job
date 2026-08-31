<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffUserStatusAckResponse extends BaseResponse
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id;

    #[ApiProperty(description: '状态：1=正常，0=禁用')]
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
