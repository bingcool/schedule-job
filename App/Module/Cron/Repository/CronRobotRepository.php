<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronRobotEntity;

class CronRobotRepository
{
    public function findById(int $id): ?CronRobotEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new CronRobotEntity())->loadById($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllRows(): array
    {
        return CronRobotEntity::query()->order('id', 'desc')->select()->toArray();
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
        $robot->delete();

        return $id;
    }
}
