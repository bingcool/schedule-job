<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronRobotEntity;
use App\Module\Common\Repository\Concerns\HydratesEntityRows;

class CronRobotRepository
{
    use HydratesEntityRows;

    public function findById(int $id): ?CronRobotEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new CronRobotEntity())->loadById($id);
    }

    /**
     * @return list<CronRobotEntity> 按 id 倒序
     */
    public function listAllRows(): array
    {
        return $this->selectRowsToEntities(
            CronRobotEntity::query()->order('id', 'desc')->select(),
            CronRobotEntity::class,
        );
    }

    public function existsByName(string $name, ?int $exceptId = null): bool
    {
        $qb = CronRobotEntity::query()->where('name', $name);
        if ($exceptId !== null && $exceptId > 0) {
            $qb->where('id', '<>', $exceptId);
        }

        return (bool) $qb->find();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): CronRobotEntity
    {
        $robot = new CronRobotEntity();
        $robot->setData($data);
        $robot->save();

        return $robot;
    }

    public function save(CronRobotEntity $robot): CronRobotEntity
    {
        $robot->save();

        return $robot;
    }

    public function delete(CronRobotEntity $robot): int
    {
        $id = (int) $robot->id;
        if (empty($id)) {
            throw new \InvalidArgumentException('Robot id is empty');
        }
        $robot->delete();

        return $id;
    }

    /**
     * 含已软删行（withoutTrashed），供节点组展示 robot 引用状态。
     *
     * @param list<int> $ids
     * @return list<CronRobotEntity>
     */
    public function listRowsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->selectRowsToEntities(
            CronRobotEntity::withoutTrashed()
                ->whereIn('id', $ids)
                ->select(),
            CronRobotEntity::class,
        );
    }
}
