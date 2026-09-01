<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class ChangePasswordAckResponse extends BaseResponse
{
    #[ApiProperty(description: '用户 ID')]
    protected int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'changed' => true,
        ];
    }
}
