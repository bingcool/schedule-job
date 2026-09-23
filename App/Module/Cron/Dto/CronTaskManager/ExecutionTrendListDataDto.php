<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronTaskManager;

use App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\ArrayList;

class ExecutionTrendListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, ExecutionTrendBucketDto>
     */
    #[ApiProperty(description: '趋势桶列表')]
    #[ArrayList(itemClass: ExecutionTrendBucketDto::class)]
    protected array $list = [];

    /**
     * @return array<int, ExecutionTrendBucketDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, ExecutionTrendBucketDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof ExecutionTrendBucketDto)) {
            throw new InvalidArgumentException('list items must be instances of ExecutionTrendBucketDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(ExecutionTrendBucketDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<ExecutionTrendBucketDto> $items
     */
    public static function fromItems(array $items): self
    {
        $dto = new self();
        foreach ($items as $item) {
            $dto->addListItem($item);
        }
        $dto->setTotal(count($dto->getList()));

        return $dto;
    }
}
