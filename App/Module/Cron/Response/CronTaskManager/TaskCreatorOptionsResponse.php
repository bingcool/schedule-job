<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\TaskCreatorOptionDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\ArrayList;
use Swoolefy\Http\BaseResponse;

class TaskCreatorOptionsResponse extends BaseResponse
{
    /**
     * @var array<int, TaskCreatorOptionDto>
     */
    #[ApiProperty(description: '创建人下拉选项')]
    #[ArrayList(
        itemClass: TaskCreatorOptionDto::class
    )]
    protected array $list = [];

    /**
     * @param array<int, TaskCreatorOptionDto> $list
     */
    public function __construct(array $list)
    {
        foreach ($list as $item) {
            $this->addListItem($item);
        }
    }

    /**
     * @return array<int, TaskCreatorOptionDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    public function addListItem(TaskCreatorOptionDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param array<int, TaskCreatorOptionDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof TaskCreatorOptionDto)) {
            throw new InvalidArgumentException('list items must be instances of TaskCreatorOptionDto');
        }
        $this->list = $list;

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
