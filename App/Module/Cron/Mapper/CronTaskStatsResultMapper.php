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
            $dto->taskId = $taskId;
            $dto->total = (int) $stats['total'];
            $dto->register = (int) $stats['register'];
            $dto->running = (int) $stats['running'];
            $dto->success = (int) $stats['success'];
            $dto->failed = (int) $stats['failed'];
            $dto->skipped = (int) $stats['skipped'];
            $dto->timeout = (int) $stats['timeout'];
            $dto->cancelled = (int) $stats['cancelled'];
            $dto->finished = (int) $stats['finished'];
            $dto->attempted = (int) $stats['attempted'];
            $dto->successRate = (float) $stats['successRate'];
            $dto->avgDurationMs = (float) $stats['avgDurationMs'];
            $dto->maxDurationMs = (float) $stats['maxDurationMs'];
            $dto->samples = (int) $stats['samples'];

            return $dto;
        }
}
