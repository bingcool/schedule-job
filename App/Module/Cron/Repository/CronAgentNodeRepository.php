<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronAgentNodeEntity;
use App\Module\Repository\Concerns\HydratesEntityRows;
use Swoolefy\Library\Db\Raw;

class CronAgentNodeRepository
{
    use HydratesEntityRows;
    public function findById(int $id): ?CronAgentNodeEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new CronAgentNodeEntity())->loadById($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRowByIdNotTrashed(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $node = CronAgentNodeEntity::withoutTrashed()->where('id', $id)->find();
        if (!$node) {
            return null;
        }

        return is_array($node) ? $node : $node->toArray();
    }

    /**
     * @param array<int, int>|null $allowedGroupIds null = 不限制
     * @return list<CronAgentNodeEntity>
     */
    public function listRowsOrdered(?array $allowedGroupIds = null): array
    {
        $qb = CronAgentNodeEntity::query()->order('id', 'desc');
        if ($allowedGroupIds !== null) {
            if ($allowedGroupIds === []) {
                return [];
            }
            $qb->whereIn('group_id', $allowedGroupIds);
        }

        return $this->selectRowsToEntities($qb->select(), CronAgentNodeEntity::class);
    }

    /**
     * @param array<int, int>|null $allowedGroupIds
     * @return list<CronAgentNodeEntity>
     */
    public function listHeartbeatRows(?array $allowedGroupIds = null): array
    {
        $qb = CronAgentNodeEntity::query()->field(['last_heartbeat_at', 'heartbeat_interval']);
        if ($allowedGroupIds !== null) {
            if ($allowedGroupIds === []) {
                return [];
            }
            $qb->whereIn('group_id', $allowedGroupIds);
        }

        return $this->selectRowsToEntities($qb->select(), CronAgentNodeEntity::class);
    }

    public function countByGroupId(int $groupId): int
    {
        if ($groupId <= 0) {
            return 0;
        }

        return (int) CronAgentNodeEntity::query()->where('group_id', $groupId)->count();
    }

    /**
     * @param list<int> $groupIds
     * @return array<int, int>
     */
    public function countGroupedByGroupIds(array $groupIds): array
    {
        $counts = [];
        if ($groupIds === []) {
            return $counts;
        }
        $rows = CronAgentNodeEntity::query()
            ->whereIn('group_id', $groupIds)
            ->field([
                new Raw('group_id'),
                new Raw('COUNT(*) AS total'),
            ])
            ->group('group_id')
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $counts[(int) ($row['group_id'] ?? 0)] = (int) ($row['total'] ?? 0);
        }

        return $counts;
    }

    /**
     * @param array<int, int> $groupIds
     * @return list<int>
     */
    public function listIdsByGroupIds(array $groupIds): array
    {
        if ($groupIds === []) {
            return [];
        }
        $nodeIds = [];
        foreach (
            CronAgentNodeEntity::query()->field(['id'])->whereIn('group_id', $groupIds)->select()->toArray() as $row
        ) {
            $nodeId = (int) ($row['id'] ?? 0);
            if ($nodeId > 0) {
                $nodeIds[] = $nodeId;
            }
        }

        return $nodeIds;
    }

    /**
     * groupId=-1 表示未分组（group_id=0）。
     *
     * @return list<int>
     */
    public function listIdsForGroupFilter(int $groupId): array
    {
        $nodeQb = CronAgentNodeEntity::query()->field(['id']);
        if ($groupId === -1) {
            $nodeQb->where('group_id', 0);
        } elseif ($groupId > 0) {
            $nodeQb->where('group_id', $groupId);
        } else {
            return [];
        }

        $nodeIds = [];
        foreach ($nodeQb->select()->toArray() as $row) {
            $nodeId = (int) ($row['id'] ?? 0);
            if ($nodeId > 0) {
                $nodeIds[] = $nodeId;
            }
        }

        return $nodeIds;
    }

    public function findGroupIdByNodeId(int $nodeId): int
    {
        if ($nodeId <= 0) {
            return 0;
        }
        $node = CronAgentNodeEntity::query()
            ->where('id', $nodeId)
            ->field(['group_id'])
            ->find();
        if (!$node) {
            return 0;
        }
        $attrs = is_array($node) ? $node : $node->getAttributes();

        return (int) ($attrs['group_id'] ?? 0);
    }

    /**
     * @param array<int, int> $nodeIds
     * @return list<CronAgentNodeEntity>
     */
    public function listMetaRowsByIds(array $nodeIds): array
    {
        if ($nodeIds === []) {
            return [];
        }

        return $this->selectRowsToEntities(
            CronAgentNodeEntity::query()
                ->whereIn('id', array_values($nodeIds))
                ->field(['id', 'group_id', 'node_name', 'last_heartbeat_at', 'heartbeat_interval'])
                ->select(),
            CronAgentNodeEntity::class,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): CronAgentNodeEntity
    {
        $node = new CronAgentNodeEntity();
        $node->setData($data);
        $node->save();

        return $node;
    }

    public function save(CronAgentNodeEntity $node): CronAgentNodeEntity
    {
        $node->save();

        return $node;
    }

    public function delete(CronAgentNodeEntity $node): int
    {
        $id = (int) $node->id;
        $node->delete();

        return $id;
    }

    /**
     * 含已软删行是否存在（用于心跳禁止「复活」已删节点）。
     */
    public function existsAnyById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return CronAgentNodeEntity::query()->where('id', $id)->limit(1)->find() !== null;
    }

    /**
     * Worker 首次心跳：节点行不存在且非软删残留时插入占位行。
     *
     * @param array<string, mixed> $row
     */
    public function insertRaw(array $row): void
    {
        CronAgentNodeEntity::query()->insert($row);
    }

    public function updateHeartbeatFields(CronAgentNodeEntity $node, string $lastHeartbeatAt, int $heartbeatInterval): void
    {
        $node->last_heartbeat_at = $lastHeartbeatAt;
        $node->heartbeat_interval = $heartbeatInterval;
        $this->save($node);
    }
}
