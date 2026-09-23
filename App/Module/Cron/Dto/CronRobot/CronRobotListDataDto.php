<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronRobot;

use App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\ArrayList;

class CronRobotListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, CronRobotRowDto>
     */
    #[ApiProperty(description: '机器人列表')]
    #[ArrayList(itemClass: CronRobotRowDto::class)]
    protected array $list = [];

    /**
     * @return array<int, CronRobotRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, CronRobotRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof CronRobotRowDto)) {
            throw new InvalidArgumentException('list items must be instances of CronRobotRowDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(CronRobotRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<CronRobotRowDto|array<string, mixed>> $items
     */
    public static function fromItems(array $items): self
    {
        $dto = new self();
        foreach ($items as $item) {
            $dto->addListItem($item instanceof CronRobotRowDto
                ? $item
                : CronRobotRowDto::fromEntityRow($item));
        }
        $dto->setTotal(count($dto->getList()));

        return $dto;
    }
}
