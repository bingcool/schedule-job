<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Common\Dto\DeleteAckDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class StaffDeleteAckResponse extends BaseResponse
{
    #[ApiProperty(description: '删除确认 data')]
    protected DeleteAckDto $data;

    public function __construct(int $id, bool $deleted = true)
    {
        $this->data = DeleteAckDto::of($id, $deleted);
    }

    public function getData(): DeleteAckDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof DeleteAckDto) {
            throw new InvalidArgumentException('data must be DeleteAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
