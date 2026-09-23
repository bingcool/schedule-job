<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronScheduledTaskRecordEntity;

class CronScheduledTaskRecordRepository
{
    public function bindExecution(int $cronId, string $scheduledAt, int $executionId): int
    {
        if ($cronId <= 0 || $scheduledAt === '' || $executionId <= 0) {
            return 0;
        }

        return (int) CronScheduledTaskRecordEntity::query()
            ->where('cron_id', $cronId)
            ->where('scheduled_at', $scheduledAt)
            ->where('execution_id', 0)
            ->update(['execution_id' => $executionId]);
    }

    public function deleteCreatedBefore(string $cutoff): int
    {
        return CronScheduledTaskRecordEntity::query()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    public function existsInSkewWindow(int $cronId, string $from, string $to): bool
    {
        if ($cronId <= 0) {
            return false;
        }

        return CronScheduledTaskRecordEntity::query()
            ->where('cron_id', $cronId)
            ->where('scheduled_at', '>=', $from)
            ->where('scheduled_at', '<=', $to)
            ->field('id')
            ->find() !== null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insert(array $row): void
    {
        CronScheduledTaskRecordEntity::query()->insert($row);
    }

    public function isDuplicateKey(\Throwable $e): bool
    {
        if ($e instanceof \PDOException) {
            $driver = (int) ($e->errorInfo[1] ?? 0);
            if ($driver === 1062) {
                return true;
            }
        }
        $msg = $e->getMessage();

        return str_contains($msg, '1062') || str_contains($msg, 'Duplicate entry');
    }
}
