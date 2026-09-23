<?php

declare(strict_types=1);

namespace App\Module\Repository\Concerns;

/**
 * 将 ORM select 结果 hydrate 为 Entity 列表（与 {@see CronRobotRepository} 约定一致）。
 */
trait HydratesEntityRows
{
    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     * @return list<T>
     */
    protected function selectRowsToEntities(iterable $rows, string $entityClass): array
    {
        $result = [];
        foreach ($rows as $row) {
            if ($row instanceof $entityClass) {
                $result[] = $row;
                continue;
            }
            $entity = new $entityClass();
            $entity->setData(is_array($row) ? $row : $row->toArray());
            $result[] = $entity;
        }

        return $result;
    }
}
