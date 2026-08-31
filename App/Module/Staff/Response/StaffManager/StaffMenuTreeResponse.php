<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffManager\StaffMenuRowDto;
use Swoolefy\Http\BaseResponse;

class StaffMenuTreeResponse extends BaseResponse
{
    /**
     * @var array<int, StaffMenuRowDto>
     */
    protected array $list = [];

    /**
     * @param array<int, array<string, mixed>|StaffMenuRowDto> $list
     */
    public function __construct(array $list)
    {
        foreach ($list as $row) {
            if ($row instanceof StaffMenuRowDto) {
                $this->list[] = $row;
            } elseif (is_array($row)) {
                $this->list[] = StaffMenuRowDto::fromEntityRow($row);
            }
        }
    }

    public function getData(): array
    {
        $rows = [];
        foreach ($this->list as $dto) {
            $rows[] = $dto->toDeepArray();
        }

        return [
            'total' => count($rows),
            'list' => $rows,
        ];
    }
}
