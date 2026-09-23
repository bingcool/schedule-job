<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronTaskRunRequestEntity;

class CronTaskRunRequestRepository
{
    /**
     * @param array<string, mixed> $row
     */
    public function insert(array $row): void
    {
        CronTaskRunRequestEntity::query()->insert($row);
    }

    public function markConsumedIfPending(int $requestId, string $consumedAt): int
    {
        if ($requestId <= 0) {
            return 0;
        }

        return (int) CronTaskRunRequestEntity::query()
            ->where('id', $requestId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => $consumedAt]);
    }

    public function findOldestPendingId(int $cronTaskId): ?int
    {
        if ($cronTaskId <= 0) {
            return null;
        }
        $row = CronTaskRunRequestEntity::query()
            ->where('cron_id', $cronTaskId)
            ->whereNull('consumed_at')
            ->order('id', 'asc')
            ->first();
        if (!$row) {
            return null;
        }
        $attrs = is_array($row) ? $row : $row->toArray();
        $id = (int) ($attrs['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @return list<int>
     */
    public function listPendingIds(int $cronTaskId): array
    {
        if ($cronTaskId <= 0) {
            return [];
        }
        $rows = CronTaskRunRequestEntity::query()
            ->where('cron_id', $cronTaskId)
            ->whereNull('consumed_at')
            ->order('id', 'asc')
            ->field(['id'])
            ->select()
            ->toArray();
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param list<int> $cronTaskIds
     * @return array<int, list<int>>
     */
    public function listPendingIdsGroupedByCronIds(array $cronTaskIds): array
    {
        $normalized = [];
        foreach ($cronTaskIds as $cronTaskId) {
            $id = (int) $cronTaskId;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }
        if ($normalized === []) {
            return [];
        }
        $rows = CronTaskRunRequestEntity::query()
            ->whereIn('cron_id', array_values($normalized))
            ->whereNull('consumed_at')
            ->order('id', 'asc')
            ->field(['id', 'cron_id'])
            ->select()
            ->toArray();
        $grouped = [];
        foreach ($rows as $row) {
            $cronId = (int) ($row['cron_id'] ?? 0);
            $requestId = (int) ($row['id'] ?? 0);
            if ($cronId <= 0 || $requestId <= 0) {
                continue;
            }
            if (!isset($grouped[$cronId])) {
                $grouped[$cronId] = [];
            }
            $grouped[$cronId][] = $requestId;
        }

        return $grouped;
    }
}
