<?php

declare(strict_types=1);

namespace App\Module\Staff\Repository;

use App\Module\Staff\Entity\StaffUserRoleEntity;
use App\Module\Staff\StaffApp;
use App\Module\Repository\Concerns\HydratesEntityRows;

class StaffUserRoleRepository
{
    use HydratesEntityRows;
    public function deleteByUserId(int $userId): int
    {
        return (int) StaffUserRoleEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('user_id', $userId)
            ->delete();
    }

    public function countByApp(): int
    {
        return (int) StaffUserRoleEntity::query()->where('app_id', StaffApp::appId())->count();
    }

    /**
     * @param array<int, int> $userIds
     * @return list<StaffUserRoleEntity>
     */
    public function listRowsByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return $this->selectRowsToEntities(
            StaffUserRoleEntity::query()
                ->where('app_id', StaffApp::appId())
                ->whereIn('user_id', $userIds)
                ->select(),
            StaffUserRoleEntity::class,
        );
    }

    /**
     * @param array<int, int> $roleIds
     * @return array<int, int>
     */
    public function countUsersGroupedByRoleIds(array $roleIds): array
    {
        $counts = [];
        if ($roleIds === []) {
            return $counts;
        }
        foreach ($this->selectRowsToEntities(
            StaffUserRoleEntity::query()
                ->where('app_id', StaffApp::appId())
                ->whereIn('role_id', $roleIds)
                ->select(),
            StaffUserRoleEntity::class,
        ) as $row) {
            $roleId = (int) $row->role_id;
            $counts[$roleId] = ($counts[$roleId] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param array<int, int> $roleIds
     */
    public function replaceForUser(int $userId, array $roleIds): void
    {
        $appId = StaffApp::appId();
        StaffUserRoleEntity::query()->where('app_id', $appId)->where('user_id', $userId)->delete();
        foreach (array_unique($roleIds) as $roleId) {
            if ($roleId <= 0) {
                continue;
            }
            $rel = new StaffUserRoleEntity();
            $rel->setData([
                'app_id' => $appId,
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
            $rel->save();
        }
    }

    /**
     * @return list<StaffUserRoleEntity>
     */
    public function listRowsByUserId(int $userId): array
    {
        return $this->selectRowsToEntities(
            StaffUserRoleEntity::query()
                ->where('app_id', StaffApp::appId())
                ->where('user_id', $userId)
                ->select(),
            StaffUserRoleEntity::class,
        );
    }
}
