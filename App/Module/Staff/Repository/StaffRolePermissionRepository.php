<?php

declare(strict_types=1);

namespace App\Module\Staff\Repository;

use App\Module\Staff\Entity\StaffRolePermissionEntity;
use App\Module\Staff\StaffApp;

class StaffRolePermissionRepository
{
    public function deleteByRoleId(int $roleId): int
    {
        return (int) StaffRolePermissionEntity::query()->where('role_id', $roleId)->delete();
    }

    public function deleteByRoleIdForApp(int $roleId): int
    {
        return (int) StaffRolePermissionEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('role_id', $roleId)
            ->delete();
    }

    /**
     * @return array<int, int>
     */
    public function listPermissionIdsByRoleAndType(int $roleId, int $type): array
    {
        $rows = StaffRolePermissionEntity::query()
            ->where('app_id', StaffApp::appId())
            ->where('role_id', $roleId)
            ->where('type', $type)
            ->select()
            ->toArray();

        return array_values(array_map(static fn (array $row): int => (int) $row['per_id'], $rows));
    }

    /**
     * @param array<int, int> $apiPerIds
     * @param array<int, int> $taskPerIds
     */
    public function replaceForRole(int $roleId, array $apiPerIds, array $taskPerIds): void
    {
        $appId = StaffApp::appId();
        StaffRolePermissionEntity::query()->where('app_id', $appId)->where('role_id', $roleId)->delete();

        $this->insertPermissions($roleId, StaffApp::PERMISSION_TYPE_API, $apiPerIds);
        $this->insertPermissions($roleId, StaffApp::PERMISSION_TYPE_TASK, $taskPerIds);
    }

    /**
     * @param array<int, int> $perIds
     */
    private function insertPermissions(int $roleId, int $type, array $perIds): void
    {
        $appId = StaffApp::appId();
        foreach (array_unique($perIds) as $perId) {
            if ($perId <= 0) {
                continue;
            }
            $rel = new StaffRolePermissionEntity();
            $rel->setData([
                'app_id' => $appId,
                'type' => $type,
                'role_id' => $roleId,
                'per_id' => $perId,
            ]);
            $rel->save();
        }
    }
}
