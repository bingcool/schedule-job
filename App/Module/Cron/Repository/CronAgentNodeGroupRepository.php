<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronAgentNodeGroupEntity;
use App\Module\Repository\Concerns\HydratesEntityRows;

class CronAgentNodeGroupRepository
{
    use HydratesEntityRows;
    public function findById(int $id): ?CronAgentNodeGroupEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new CronAgentNodeGroupEntity())->loadById($id);
    }

    /**
     * @return list<CronAgentNodeGroupEntity>
     */
    public function listAdminRows(): array
    {
        return $this->selectRowsToEntities(
            CronAgentNodeGroupEntity::query()
                ->field(['id', 'group_name', 'robot_id', 'remark', 'created_at', 'updated_at'])
                ->order('id', 'desc')
                ->select(),
            CronAgentNodeGroupEntity::class,
        );
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array{id:int,groupName:string}>
     */
    public function briefMapByIds(array $ids): array
    {
        $map = [];
        if ($ids === []) {
            return $map;
        }
        $rows = CronAgentNodeGroupEntity::query()->whereIn('id', $ids)->select()->toArray();
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $map[$id] = [
                'id' => $id,
                'groupName' => (string) ($row['group_name'] ?? ''),
            ];
        }

        return $map;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, string>
     */
    public function groupNameMapByIds(array $ids): array
    {
        $names = [];
        if ($ids === []) {
            return $names;
        }
        $rows = CronAgentNodeGroupEntity::query()
            ->whereIn('id', array_values($ids))
            ->field(['id', 'group_name'])
            ->select()
            ->toArray();
        foreach ($rows as $group) {
            $names[(int) ($group['id'] ?? 0)] = (string) ($group['group_name'] ?? $group['groupName'] ?? '');
        }

        return $names;
    }

    public function countExistingIds(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return count(CronAgentNodeGroupEntity::query()->whereIn('id', $ids)->select()->toArray());
    }

    public function countByRobotId(int $robotId): int
    {
        if ($robotId <= 0) {
            return 0;
        }

        return (int) CronAgentNodeGroupEntity::query()->where('robot_id', $robotId)->count();
    }

    public function existsByGroupName(string $name, ?int $exceptId = null): bool
    {
        $qb = CronAgentNodeGroupEntity::query()->where('group_name', $name);
        if ($exceptId !== null && $exceptId > 0) {
            $qb->where('id', '<>', $exceptId);
        }

        return $qb->count() > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): CronAgentNodeGroupEntity
    {
        $group = new CronAgentNodeGroupEntity();
        $group->setData($data);
        $group->save();

        return $group;
    }

    public function save(CronAgentNodeGroupEntity $group): CronAgentNodeGroupEntity
    {
        $group->save();

        return $group;
    }

    public function delete(CronAgentNodeGroupEntity $group): int
    {
        $id = (int) $group->id;
        $group->delete();

        return $id;
    }

    public function findRowById(int $id): ?array
    {
        $group = $this->findById($id);

        return $group?->getAttributes();
    }
}
