<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Cron\Dto\CronTaskManager\CronNodeListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class CronNodeListResponse extends BaseListResponse
{
    #[ApiProperty(description: '节点列表 data')]
    protected CronNodeListDataDto $data;

    /**
     * @param CronNodeListDataDto|list<\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto|array<string, mixed>> $list
     */
    public function __construct(CronNodeListDataDto|array $list)
    {
        $this->data = $list instanceof CronNodeListDataDto
            ? $list
            : CronNodeListDataDto::fromItems($list);
    }

    public function getData(): CronNodeListDataDto
    {
        return $this->data;
    }

    /**
     * @param CronNodeListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof CronNodeListDataDto) {
            throw new InvalidArgumentException('data must be CronNodeListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
