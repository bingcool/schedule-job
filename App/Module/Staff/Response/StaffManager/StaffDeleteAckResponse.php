<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffDeleteAckResponse extends BaseResponse
{
    #[ApiProperty(description: '记录 ID')]
    protected int $id;

    #[ApiProperty(description: '是否已删除')]
    protected bool $deleted;

    public function __construct(int $id, bool $deleted = true)
    {
        $this->id = $id;
        $this->deleted = $deleted;
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'deleted' => $this->deleted,
        ];
    }
}
