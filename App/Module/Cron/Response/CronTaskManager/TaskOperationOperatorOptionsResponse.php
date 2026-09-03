<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\TaskOperationOperatorOptionDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\ArrayList;
use Swoolefy\Http\BaseResponse;

class TaskOperationOperatorOptionsResponse extends BaseResponse
{
    /**
     * @var array<int, TaskOperationOperatorOptionDto>
     */
    #[ApiProperty(description: '操作人下拉选项')]
    #[ArrayList(
        itemClass: TaskOperationOperatorOptionDto::class
    )]
    protected array $list = [];

    /**
     * @param array<int, TaskOperationOperatorOptionDto> $list
     */
    public function __construct(array $list)
    {
        foreach ($list as $item) {
            $this->addListItem($item);
        }
    }

    public function addListItem(TaskOperationOperatorOptionDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    public function getTotal(): int
    {
        return count($this->list);
    }

    public function getData(): array
    {
        $rows = [];
        foreach ($this->list as $dto) {
            $rows[] = $dto->toDeepArray();
        }

        return [
            'total' => $this->getTotal(),
            'list' => $rows,
        ];
    }
}
