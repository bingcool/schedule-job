<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronTaskLogEntity;
use App\Module\Repository\Concerns\HydratesEntityRows;
use Swoolefy\Library\Db\Query;
use Swoolefy\Worker\Cron\ExecutionStatus;

class CronTaskLogRepository
{
    use HydratesEntityRows;
    public function newQuery(): Query
    {
        return CronTaskLogEntity::query();
    }

    /**
     * Dashboard / taskStats：Execution 行（exec_batch_id 非空），不含 TEXT 列。
     */
    public function newExecutionStatsQuery(): Query
    {
        return CronTaskLogEntity::query()
            ->whereNotNull('exec_batch_id')
            ->where('exec_batch_id', '<>', '');
    }

    public function deleteCreatedBefore(string $cutoff): int
    {
        return CronTaskLogEntity::withoutTrashed()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestRowByCronIdAndExecBatchId(int $cronId, string $execBatchId): ?array
    {
        if ($cronId <= 0 || $execBatchId === '') {
            return null;
        }
        $row = CronTaskLogEntity::query()
            ->where([
                'cron_id' => $cronId,
                'exec_batch_id' => $execBatchId,
            ])
            ->order('id', 'desc')
            ->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insert(array $row): void
    {
        CronTaskLogEntity::query()->insert($row);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRowById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = CronTaskLogEntity::query()->where('id', $id)->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRowByBatch(int $cronId, string $execBatchId): ?array
    {
        if ($cronId <= 0 || $execBatchId === '') {
            return null;
        }
        $row = CronTaskLogEntity::query()
            ->where([
                'cron_id' => $cronId,
                'exec_batch_id' => $execBatchId,
            ])
            ->order('id', 'asc')
            ->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestRowByRequestId(int $requestId): ?array
    {
        $row = CronTaskLogEntity::query()
            ->where('request_id', $requestId)
            ->order('id', 'desc')
            ->find();
        if (!$row) {
            return null;
        }

        return is_array($row) ? $row : $row->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateMessage(int $logId, string $message): int
    {
        return (int) CronTaskLogEntity::query()
            ->where('id', $logId)
            ->update(['message' => $message]);
    }

    /**
     * @param array<string, mixed>|mixed $taskItem
     */
    public function updateTaskItem(int $logId, mixed $taskItem): int
    {
        return (int) CronTaskLogEntity::query()
            ->where('id', $logId)
            ->update(['task_item' => $taskItem]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function casHeartbeat(int $logId, string $owner, array $data): int
    {
        return (int) CronTaskLogEntity::query()
            ->where('id', $logId)
            ->whereIn('status', [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED])
            ->where('lease_owner', $owner)
            ->update($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function casTransition(int $id, int $fromStatus, array $data): int
    {
        return (int) CronTaskLogEntity::query()
            ->where('id', $id)
            ->where('status', $fromStatus)
            ->update($data);
    }

    /**
     * Recovery CAS：id + status + lease_owner + lease_until + expiry。
     *
     * @param array<string, mixed> $data
     */
    public function casRecoverLease(
        int $id,
        int $fromStatus,
        string $oldOwner,
        string $oldLeaseUntil,
        string $recoveryNow,
        array $data,
    ): int {
        return (int) CronTaskLogEntity::query()
            ->where('id', $id)
            ->where('status', $fromStatus)
            ->where('lease_owner', $oldOwner)
            ->where('lease_until', $oldLeaseUntil)
            ->where('lease_until', '<', $recoveryNow)
            ->update($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateWhileRunningOrCancel(int $id, array $data): int
    {
        return (int) CronTaskLogEntity::query()
            ->where('id', $id)
            ->whereIn('status', [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED])
            ->update($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateByIdAndStatus(int $id, int $fromStatus, array $data): int
    {
        return (int) CronTaskLogEntity::query()
            ->where('id', $id)
            ->where('status', $fromStatus)
            ->update($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateById(int $id, array $data): int
    {
        return (int) CronTaskLogEntity::query()->where('id', $id)->update($data);
    }

    /**
     * @return list<CronTaskLogEntity>
     */
    public function listExpiredLeaseRows(int $limit, string $now): array
    {
        return $this->selectRowsToEntities(
            CronTaskLogEntity::query()
                ->whereIn('status', [ExecutionStatus::RUNNING, ExecutionStatus::CANCEL_REQUESTED])
                ->whereNotNull('lease_until')
                ->where('lease_until', '<', $now)
                ->order('id', 'asc')
                ->limit($limit)
                ->select(),
            CronTaskLogEntity::class,
        );
    }
}
