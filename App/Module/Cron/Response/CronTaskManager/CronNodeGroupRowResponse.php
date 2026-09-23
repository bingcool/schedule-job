<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\CronAgentNodeGroupRowDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class CronNodeGroupRowResponse extends BaseResponse
{
    #[ApiProperty(description: '节点分组详情 data')]
    protected CronAgentNodeGroupRowDto $data;

    public function __construct(CronAgentNodeGroupRowDto|array $attributes)
    {
        $this->data = $attributes instanceof CronAgentNodeGroupRowDto
            ? $attributes
            : CronAgentNodeGroupRowDto::fromEntityRow($attributes);
    }

    public function getData(): CronAgentNodeGroupRowDto
    {
        return $this->data;
    }

    public function setData($data): static
    {
        if (!$data instanceof CronAgentNodeGroupRowDto) {
            throw new InvalidArgumentException('data must be CronAgentNodeGroupRowDto');
        }
        $this->data = $data;

        return $this;
    }
}
