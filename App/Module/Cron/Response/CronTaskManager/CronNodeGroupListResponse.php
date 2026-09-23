<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Cron\Dto\CronTaskManager\CronNodeGroupListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class CronNodeGroupListResponse extends BaseListResponse
{
    #[ApiProperty(description: '节点分组列表 data')]
    protected CronNodeGroupListDataDto $data;

    /**
     * @param CronNodeGroupListDataDto|list<\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeGroupRowDto|array<string, mixed>> $list
     */
    public function __construct(CronNodeGroupListDataDto|array $list)
    {
        $this->data = $list instanceof CronNodeGroupListDataDto
            ? $list
            : CronNodeGroupListDataDto::fromItems($list);
    }

    public function getData(): CronNodeGroupListDataDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof CronNodeGroupListDataDto) {
            throw new InvalidArgumentException('data must be CronNodeGroupListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
