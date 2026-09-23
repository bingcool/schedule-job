<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Dto\CronTaskManager\ListTasksQueryDto;
use App\Module\Cron\Entity\CronTaskEntity;
use App\Module\Repository\Concerns\HydratesEntityRows;
use Swoolefy\Library\Db\Query;
use Swoolefy\Library\Db\Raw;

class CronTaskRepository
{
    use HydratesEntityRows;
    /** @var list<string> */
    private const LIST_TASK_FIELDS = [
        'id',
        'node_id',
        'cron_name',
        'expression',
        'command',
        'exec_type',
        'status',
        'with_block_lapping',
        'retry',
        'timeout',
        'description',
        'cron_between',
        'cron_skip',
        'http_method',
        'http_body',
        'http_headers',
        'http_request_time_out',
        'k8s_spec',
        'created_by',
        'created_at',
        'updated_at',
    ];

    public function findById(int $id): ?CronTaskEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new CronTaskEntity())->loadById($id);
    }

    public function findByIdForUpdate(int $id): ?CronTaskEntity
    {
        if ($id <= 0) {
            return null;
        }
        $row = CronTaskEntity::query()
            ->where('id', $id)
            ->setOption('lock', true)
            ->find();
        if (!$row) {
            return null;
        }
        if ($row instanceof CronTaskEntity) {
            return $row;
        }
        $task = new CronTaskEntity();
        $task->setData(is_array($row) ? $row : $row->toArray());

        return $task;
    }

    public function findRowById(int $id): ?array
    {
        $task = $this->findById($id);

        return $task?->getAttributes();
    }

    /**
     * Agent 拉任务：node_id + exec_type，含全部列（含 status，供 Runtime Diff）。
     *
     * @return list<CronTaskEntity>
     */
    public function listFullRowsByNodeIdAndExecType(int $nodeId, int $execType): array
    {
        if ($nodeId <= 0) {
            return [];
        }

        return $this->selectRowsToEntities(
            CronTaskEntity::query()->field('*')->where([
                'node_id' => $nodeId,
                'exec_type' => $execType,
            ])->select(),
            CronTaskEntity::class,
        );
    }

    public function countAll(): int
    {
        return (int) CronTaskEntity::query()->count();
    }

    public function countEnabled(): int
    {
        return (int) CronTaskEntity::query()->where('status', 1)->count();
    }

    /**
     * @param list<int>|null $scopedNodeIds null=不限制；[]=0
     */
    public function countWithNodeScope(?array $scopedNodeIds): int
    {
        if ($scopedNodeIds !== null && $scopedNodeIds === []) {
            return 0;
        }
        $qb = CronTaskEntity::query();
        if ($scopedNodeIds !== null) {
            $qb->whereIn('node_id', $scopedNodeIds);
        }

        return (int) $qb->count();
    }

    /**
     * @param list<int>|null $scopedNodeIds
     */
    public function countEnabledWithNodeScope(?array $scopedNodeIds): int
    {
        if ($scopedNodeIds !== null && $scopedNodeIds === []) {
            return 0;
        }
        $qb = CronTaskEntity::query()->where('status', 1);
        if ($scopedNodeIds !== null) {
            $qb->whereIn('node_id', $scopedNodeIds);
        }

        return (int) $qb->count();
    }

    public function countByNodeId(int $nodeId): int
    {
        if ($nodeId <= 0) {
            return 0;
        }

        return (int) CronTaskEntity::query()->where('node_id', $nodeId)->count();
    }

    /**
     * @param list<int> $nodeIds
     * @return array<int, int>
     */
    public function countGroupedByNodeIds(array $nodeIds): array
    {
        $counts = [];
        if ($nodeIds === []) {
            return $counts;
        }
        $rows = CronTaskEntity::query()
            ->whereIn('node_id', $nodeIds)
            ->field([
                new Raw('node_id'),
                new Raw('COUNT(*) AS total'),
            ])
            ->group('node_id')
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $counts[(int) ($row['node_id'] ?? 0)] = (int) ($row['total'] ?? 0);
        }

        return $counts;
    }

    /**
     * @param list<int> $nodeIds
     * @return array<int, int>
     */
    public function listIdMapByNodeIds(array $nodeIds): array
    {
        $taskIds = [];
        if ($nodeIds === []) {
            return $taskIds;
        }
        foreach (
            CronTaskEntity::query()->field(['id'])->whereIn('node_id', $nodeIds)->select()->toArray() as $row
        ) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $taskIds[$id] = $id;
            }
        }

        return $taskIds;
    }

    public function existsByCronName(string $name, ?int $exceptId = null): bool
    {
        $qb = CronTaskEntity::query()->where('cron_name', $name);
        if ($exceptId !== null && $exceptId > 0) {
            $qb->where('id', '<>', $exceptId);
        }

        return $qb->count() > 0;
    }

    /**
     * @param list<int> $ids
     * @return list<CronTaskEntity>
     */
    public function listRowsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->selectRowsToEntities(
            CronTaskEntity::query()->whereIn('id', $ids)->select(),
            CronTaskEntity::class,
        );
    }

    /**
     * @param list<int> $ids
     */
    public function updateStatusByIds(array $ids, int $status): int
    {
        if ($ids === []) {
            return 0;
        }

        return (int) CronTaskEntity::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function countByListQuery(ListTasksQueryDto $query, ?array $scopedNodeIds): int
    {
        return (int) $this->listTasksQueryBuilder($query, $scopedNodeIds)->count();
    }

    /**
     * @param list<int>|null $scopedNodeIds
     * @return list<CronTaskEntity>
     */
    public function listRowsByListQuery(ListTasksQueryDto $query, ?array $scopedNodeIds): array
    {
        return $this->selectRowsToEntities(
            $this->listTasksQueryBuilder($query, $scopedNodeIds)
                ->order('id', 'desc')
                ->limit($query->getOffset(), $query->getPageSize())
                ->select(),
            CronTaskEntity::class,
        );
    }

    /**
     * @param list<int>|null $scopedNodeIds
     */
    private function listTasksQueryBuilder(ListTasksQueryDto $query, ?array $scopedNodeIds): Query
    {
        $keyword = trim((string) ($query->getKeyword() ?? ''));
        $status = $query->getStatus();
        $nodeId = $query->getNodeId();
        $execType = $query->getExecType();
        $createdBy = $query->getCreatedBy();

        $qb = CronTaskEntity::query()->field(self::LIST_TASK_FIELDS);
        if ($keyword !== '') {
            $qb->where('cron_name', 'like', '%' . $keyword . '%');
        }
        if ($status !== null) {
            $qb->where('status', $status);
        }
        if ($nodeId !== null) {
            $qb->where('node_id', $nodeId);
        }
        if ($scopedNodeIds !== null) {
            $qb->whereIn('node_id', $scopedNodeIds);
        }
        if ($execType !== null) {
            $qb->where('exec_type', $execType);
        }
        if ($createdBy !== null && $createdBy > 0) {
            $qb->where('created_by', $createdBy);
        }

        return $qb;
    }

    /**
     * @param list<int>|null $scopedNodeIds
     * @return array<int, array<string, mixed>>
     */
    public function listCreatorGroupRows(?array $scopedNodeIds): array
    {
        $qb = CronTaskEntity::query()
            ->field([new Raw('created_by')])
            ->where('created_by', '>', 0)
            ->group('created_by');
        if ($scopedNodeIds !== null) {
            if ($scopedNodeIds === []) {
                return [];
            }
            $qb->whereIn('node_id', $scopedNodeIds);
        }

        return $qb->order('created_by', 'asc')->select()->toArray();
    }

    /**
     * @param list<int>|null $scopedNodeIds
     * @return array<int, int>
     */
    public function listIdMapByExecTypeAndName(?int $execType, ?string $taskName, ?array $scopedNodeIds): array
    {
        $qb = CronTaskEntity::query()->field(['id']);
        if ($scopedNodeIds !== null) {
            if ($scopedNodeIds === []) {
                return [];
            }
            $qb->whereIn('node_id', $scopedNodeIds);
        }
        if ($execType !== null) {
            $qb->where('exec_type', $execType);
        }
        if ($taskName !== null && $taskName !== '') {
            $qb->where('cron_name', 'like', '%' . $taskName . '%');
        }
        $taskIds = [];
        foreach ($qb->select()->toArray() as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $taskIds[$id] = $id;
            }
        }

        return $taskIds;
    }

    /**
     * @param list<int> $cronIds
     * @return array<int, array{task_name:string,exec_type:int}>
     */
    public function mapMetaByCronIds(array $cronIds): array
    {
        if ($cronIds === []) {
            return [];
        }
        $rows = CronTaskEntity::query()
            ->whereIn('id', $cronIds)
            ->field(['id', 'cron_name', 'exec_type'])
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $map[$id] = [
                'task_name' => (string) ($row['cron_name'] ?? ''),
                'exec_type' => (int) ($row['exec_type'] ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): CronTaskEntity
    {
        $task = new CronTaskEntity();
        $task->setData($data);
        $task->save();

        return $task;
    }

    public function save(CronTaskEntity $task): CronTaskEntity
    {
        $task->save();

        return $task;
    }

    public function delete(CronTaskEntity $task): int
    {
        $id = (int) $task->id;
        $task->delete();

        return $id;
    }

    public function getConnection(): mixed
    {
        return (new CronTaskEntity())->getConnection();
    }
}
