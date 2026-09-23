<?php

declare(strict_types=1);

namespace App\Module\Staff\Repository;

use App\Module\Staff\Entity\StaffUserRelateNodeGroupEntity;
use App\Module\Common\Repository\Concerns\HydratesEntityRows;

class StaffUserRelateNodeGroupRepository
{
    use HydratesEntityRows;
    public function deleteByUserId(int $userId): int
    {
        return (int) StaffUserRelateNodeGroupEntity::query()->where('user_id', $userId)->delete();
    }

    public function existsForUserAndGroup(int $userId, int $nodeGroupId): bool
    {
        if ($userId <= 0 || $nodeGroupId <= 0) {
            return false;
        }

        return StaffUserRelateNodeGroupEntity::query()
            ->where('user_id', $userId)
            ->where('node_group_id', $nodeGroupId)
            ->count() > 0;
    }

    /**
     * @return array<int, int>
     */
    public function listUserIdsByNodeGroupId(int $nodeGroupId): array
    {
        if ($nodeGroupId <= 0) {
            return [];
        }

        $rows = StaffUserRelateNodeGroupEntity::query()
            ->where('node_group_id', $nodeGroupId)
            ->field(['user_id'])
            ->select()
            ->toArray();
        $userIds = [];
        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId > 0) {
                $userIds[$userId] = $userId;
            }
        }

        return array_values($userIds);
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, array<int, int>>
     */
    public function nodeGroupIdsGroupedByUserIds(array $userIds): array
    {
        $grouped = [];
        if ($userIds === []) {
            return $grouped;
        }
        foreach ($this->selectRowsToEntities(
            StaffUserRelateNodeGroupEntity::query()->whereIn('user_id', $userIds)->select(),
            StaffUserRelateNodeGroupEntity::class,
        ) as $row) {
            $grouped[(int) $row->user_id][] = (int) $row->node_group_id;
        }

        return $grouped;
    }

    /**
     * @param array<int, int> $groupIds
     */
    public function replaceForUser(int $userId, array $groupIds): void
    {
        StaffUserRelateNodeGroupEntity::query()->where('user_id', $userId)->delete();
        foreach (array_unique($groupIds) as $groupId) {
            if ($groupId <= 0) {
                continue;
            }
            $rel = new StaffUserRelateNodeGroupEntity();
            $rel->setData([
                'user_id' => $userId,
                'node_group_id' => $groupId,
            ]);
            $rel->save();
        }
    }
}
