<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskStatsResultDto;
use Swoolefy\Worker\Cron\ExecutionStatus;

final class CronTaskStatsResultMapper
{
    /**
     * 由 GROUP BY 聚合结果构造。空数据返回完整零值结构。
     *
     * @param array<string, mixed> $stats {@see ExecutionStatus::aggregateCounts()}
     */
    public static function fromAggregated(int $taskId, array $stats): CronTaskStatsResultDto
    {
        $empty = ExecutionStatus::emptyCounts();
        $stats = array_merge($empty, $stats);
        $dto = new CronTaskStatsResultDto();
        $dto->setTaskId($taskId);
        $dto->setTotal((int) $stats['total']);
        $dto->setRegister((int) $stats['register']);
        $dto->setRunning((int) $stats['running']);
        $dto->setSuccess((int) $stats['success']);
        $dto->setFailed((int) $stats['failed']);
        $dto->setSkipped((int) $stats['skipped']);
        $dto->setTimeout((int) $stats['timeout']);
        $dto->setCancelled((int) $stats['cancelled']);
        $dto->setFinished((int) $stats['finished']);
        $dto->setAttempted((int) $stats['attempted']);
        $dto->setSuccessRate((float) $stats['successRate']);
        $dto->setAvgDurationMs((float) $stats['avgDurationMs']);
        $dto->setMaxDurationMs((float) $stats['maxDurationMs']);
        $dto->setSamples((int) $stats['samples']);

        return $dto;
    }
}
