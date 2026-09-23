<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronRobot;

use App\Module\Cron\Dto\CronRobot\CronRobotRowDto;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\ArrayList;
use Swoolefy\Http\BaseResponse;

class CronRobotListResponse extends BaseResponse
{
    /**
     * @var array<int, CronRobotRowDto>
     */
    #[ApiProperty(description: '机器人列表')]
    #[ArrayList(itemClass: CronRobotRowDto::class)]
    protected array $list = [];

    /**
     * @param list<CronRobotRowDto|array<string, mixed>> $list
     */
    public function __construct(array $list)
    {
        foreach ($list as $row) {
            $this->list[] = $row instanceof CronRobotRowDto
                ? $row
                : CronRobotRowDto::fromEntityRow($row);
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
