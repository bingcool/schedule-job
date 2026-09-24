<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * 执行趋势查询 DTO。
 */
class ExecutionTrendQueryDto extends AbstractDto
{
    #[ApiProperty(description: '24h / 7d / 15d')]
    protected string $range = '24h';

    public static function of(string $range): self
    {
        $dto = new self();
        $dto->setRange($range);

        return $dto;
    }

    public function getRange(): string
    {
        return $this->range;
    }

    public function setRange(string $range): static
    {
        $range = strtolower(trim($range));
        $this->range = in_array($range, ['24h', '7d', '15d'], true) ? $range : '24h';

        return $this;
    }
}
