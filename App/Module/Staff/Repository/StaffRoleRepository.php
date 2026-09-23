<?php

declare(strict_types=1);

namespace App\Module\Staff\Repository;

use App\Module\Staff\Dto\StaffRole\ListRolesQueryDto;
use App\Module\Staff\Entity\StaffRoleEntity;
use App\Module\Staff\StaffApp;
use App\Module\Repository\Concerns\HydratesEntityRows;
use Swoolefy\Library\Db\Query;

class StaffRoleRepository
{
    use HydratesEntityRows;
    public function findById(int $id): ?StaffRoleEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new StaffRoleEntity())->loadById($id);
    }

    public function findByCode(string $code): ?StaffRoleEntity
    {
        return (new StaffRoleEntity())->loadByCode($code);
    }

    public function countByListQuery(ListRolesQueryDto $query): int
    {
        return (int) $this->listQueryBuilder($query)->count();
    }

    /**
     * @return list<StaffRoleEntity>
     */
    public function listRowsByListQuery(ListRolesQueryDto $query): array
    {
        return $this->selectRowsToEntities(
            $this->listQueryBuilder($query)
                ->order('id', 'desc')
                ->limit($query->getOffset(), $query->getPageSize())
                ->select(),
            StaffRoleEntity::class,
        );
    }

    /**
     * @return list<StaffRoleEntity>
     */
    public function listAllRowsForApp(): array
    {
        return $this->selectRowsToEntities(
            StaffRoleEntity::query()->where('app_id', StaffApp::appId())->select(),
            StaffRoleEntity::class,
        );
    }

    /**
     * @return list<StaffRoleEntity>
     */
    public function listEnabledOptionRows(): array
    {
        return $this->selectRowsToEntities(
            StaffRoleEntity::query()
                ->where('app_id', StaffApp::appId())
                ->where('status', 1)
                ->order('id', 'asc')
                ->select(),
            StaffRoleEntity::class,
        );
    }

    /**
     * @param array<int, int> $roleIds
     * @return list<StaffRoleEntity>
     */
    public function listRowsByIdsForApp(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        return $this->selectRowsToEntities(
            StaffRoleEntity::query()
                ->whereIn('id', $roleIds)
                ->where('app_id', StaffApp::appId())
                ->select(),
            StaffRoleEntity::class,
        );
    }

    /**
     * @param array<int, int> $roleIds
     * @return list<StaffRoleEntity>
     */
    public function listEnabledRowsByIds(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        return $this->selectRowsToEntities(
            StaffRoleEntity::query()
                ->where('app_id', StaffApp::appId())
                ->whereIn('id', $roleIds)
                ->where('status', 1)
                ->select(),
            StaffRoleEntity::class,
        );
    }

    /**
     * @param array<int, int> $roleIds
     */
    public function countExistingForApp(array $roleIds): int
    {
        if ($roleIds === []) {
            return 0;
        }

        return count($this->listRowsByIdsForApp($roleIds));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): StaffRoleEntity
    {
        $role = new StaffRoleEntity();
        $role->setData($data);
        $role->save();

        return $role;
    }

    public function save(StaffRoleEntity $role): StaffRoleEntity
    {
        $role->save();

        return $role;
    }

    public function delete(StaffRoleEntity $role): int
    {
        $id = (int) $role->id;
        $role->delete();

        return $id;
    }

    private function listQueryBuilder(ListRolesQueryDto $query): Query
    {
        $name = trim((string) ($query->getName() ?? ''));
        $status = $query->getStatus();
        $appId = StaffApp::appId();

        $qb = StaffRoleEntity::query()->where('app_id', $appId);
        if ($name !== '') {
            $qb->where('name', 'like', '%' . $name . '%');
        }
        if ($status !== null) {
            $qb->where('status', $status);
        }

        return $qb;
    }
}
