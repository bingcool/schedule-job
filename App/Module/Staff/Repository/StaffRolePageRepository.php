<?php

declare(strict_types=1);

namespace App\Module\Staff\Repository;

use App\Module\Staff\Entity\StaffRolePageEntity;
use App\Module\Staff\StaffApp;
use App\Module\Repository\Concerns\HydratesEntityRows;

class StaffRolePageRepository
{
    use HydratesEntityRows;
    public function deleteByRoleId(int $roleId): int
    {
        return (int) StaffRolePageEntity::query()->where('role_id', $roleId)->delete();
    }

    public function deleteByPageId(int $pageId): int
    {
        return (int) StaffRolePageEntity::query()->where('page_id', $pageId)->delete();
    }

    public function deleteByRoleIdForApp(int $roleId): int
    {
        return (int) StaffRolePageEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('role_id', $roleId)
            ->delete();
    }

    /**
     * @param array<int, int> $roleIds
     * @return list<StaffRolePageEntity>
     */
    public function listRowsByRoleIds(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        return $this->selectRowsToEntities(
            StaffRolePageEntity::query()
                ->where('app_id', StaffApp::appId())
                ->whereIn('role_id', $roleIds)
                ->select(),
            StaffRolePageEntity::class,
        );
    }

    /**
     * @return array<int, int>
     */
    public function listPageIdsByRoleId(int $roleId): array
    {
        $rows = StaffRolePageEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('role_id', $roleId)
            ->select()
            ->toArray();

        return array_values(array_map(static fn (array $row): int => (int) $row['page_id'], $rows));
    }

    /**
     * @param array<int, int> $roleIds
     * @return array<int, int>
     */
    public function countPagesGroupedByRoleIds(array $roleIds): array
    {
        $counts = [];
        if ($roleIds === []) {
            return $counts;
        }
        foreach ($this->listRowsByRoleIds($roleIds) as $row) {
            $roleId = (int) $row->role_id;
            $counts[$roleId] = ($counts[$roleId] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param array<int, int> $pageIds
     */
    public function replaceForRole(int $roleId, array $pageIds): void
    {
        $appId = StaffApp::appId();
        StaffRolePageEntity::query()->where('app_id', $appId)->where('role_id', $roleId)->delete();

        foreach (array_unique($pageIds) as $pageId) {
            if ($pageId <= 0) {
                continue;
            }
            $rel = new StaffRolePageEntity();
            $rel->setData([
                'app_id' => $appId,
                'role_id' => $roleId,
                'page_id' => $pageId,
            ]);
            $rel->save();
        }
    }
}
