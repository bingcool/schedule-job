<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronAgentNodeGroupEntity;

class CronAgentNodeGroupRepository
{
    public function findById(int $id): ?CronAgentNodeGroupEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new CronAgentNodeGroupEntity())->loadById($id);
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
}
