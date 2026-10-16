<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronRobotAlertLogEntity;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\RobotAlertLogsQueryDto;
use Swoolefy\Library\Db\Query;

class CronRobotAlertLogRepository
{
    /**
     * @param array<string, mixed> $row
     */
    public function insert(array $row): void
    {
        CronRobotAlertLogEntity::query()->insert($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertIgnoringDuplicateKey(array $row): void
    {
        try {
            $this->insert($row);
        } catch (\Throwable $e) {
            if ($this->isDuplicateKey($e)) {
                return;
            }
            throw $e;
        }
    }

    public function existsByDedupKey(string $dedupKey): bool
    {
        if ($dedupKey === '') {
            return false;
        }

        return CronRobotAlertLogEntity::query()
            ->where('dedup_key', $dedupKey)
            ->limit(1)
            ->find() !== null;
    }

    /**
     * 硬删除早于 cutoff 的投递记录。
     */
    public function deleteCreatedBefore(string $cutoff): int
    {
        return (int) CronRobotAlertLogEntity::query()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    public function isDuplicateKey(\Throwable $e): bool
    {
        $code = $e->getCode();
        if ($code === 23000 || $code === '23000' || (int) $code === 1062) {
            return true;
        }
        $msg = $e->getMessage();
        if (str_contains($msg, '1062') || str_contains($msg, 'uk_execution_id') || str_contains($msg, 'uk_dedup_key') || str_contains($msg, 'Duplicate')) {
            return true;
        }
        $prev = $e->getPrevious();
        if ($prev instanceof \PDOException) {
            return (int) ($prev->errorInfo[1] ?? 0) === 1062;
        }

        return false;
    }

    /**
     * @param list<int>|null $scopedNodeIds null = 不限制；[] = 无可见节点
     */
    public function countByQuery(RobotAlertLogsQueryDto $query, ?array $scopedNodeIds): int
    {
        $qb = $this->filteredQuery($query, $scopedNodeIds);
        if ($qb === null) {
            return 0;
        }

        return (int) $qb->count();
    }

    /**
     * @param list<int>|null $scopedNodeIds
     * @return list<array<string, mixed>>
     */
    public function listRowsByQuery(RobotAlertLogsQueryDto $query, ?array $scopedNodeIds): array
    {
        $qb = $this->filteredQuery($query, $scopedNodeIds);
        if ($qb === null) {
            return [];
        }

        return $qb->order('id', 'desc')
            ->limit($query->getOffset(), $query->getPageSize())
            ->select()
            ->toArray();
    }

    /**
     * @param list<int>|null $scopedNodeIds
     */
    private function filteredQuery(RobotAlertLogsQueryDto $query, ?array $scopedNodeIds): ?Query
    {
        if ($scopedNodeIds !== null && $scopedNodeIds === []) {
            return null;
        }
        $qb = CronRobotAlertLogEntity::query();
        if ($scopedNodeIds !== null) {
            $qb->whereIn('node_id', $scopedNodeIds);
        }
        $robotId = $query->getRobotId();
        if ($robotId !== null) {
            $qb->where('robot_id', $robotId);
        }
        $alertType = $query->getAlertType();
        if ($alertType !== null) {
            $qb->where('alert_type', $alertType);
        }
        $status = $query->getStatus();
        if ($status !== null) {
            $qb->where('status', $status);
        }
        $nodeId = $query->getNodeId();
        if ($nodeId !== null) {
            $qb->where('node_id', $nodeId);
        }
        $startTime = $query->getStartTime();
        if ($startTime !== null) {
            $qb->where('created_at', '>=', $startTime);
        }
        $endTime = $query->getEndTime();
        if ($endTime !== null) {
            $qb->where('created_at', '<=', $endTime);
        }

        return $qb;
    }
}
