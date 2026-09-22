<?php

declare(strict_types=1);

namespace App\Module\Staff\Repository;

use App\Module\Staff\Dto\StaffManager\ListUsersQueryDto;
use App\Module\Staff\Entity\StaffUserEntity;
use Swoolefy\Library\Db\Query;

class StaffUserRepository
{
    public function findById(int $id): ?StaffUserEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new StaffUserEntity())->loadById($id);
    }

    public function findByLoginIdentity(string $identity): ?StaffUserEntity
    {
        return (new StaffUserEntity())->loadByLoginIdentity($identity);
    }

    public function existsAccount(string $account, ?int $exceptId = null): bool
    {
        $qb = StaffUserEntity::query()->where('account', $account);
        if ($exceptId !== null && $exceptId > 0) {
            $qb->where('id', '<>', $exceptId);
        }

        return (bool) $qb->find();
    }

    public function findIdUsingEmail(string $email, ?int $exceptId = null): ?int
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        $emailQb = StaffUserEntity::query()->where('email', $email);
        $accountQb = StaffUserEntity::query()->where('account', $email);
        if ($exceptId !== null && $exceptId > 0) {
            $emailQb->where('id', '<>', $exceptId);
            $accountQb->where('id', '<>', $exceptId);
        }
        $row = $emailQb->field(['id'])->find();
        if ($row) {
            return (int) ($row['id'] ?? 0) ?: null;
        }
        $row = $accountQb->field(['id'])->find();
        if ($row) {
            return (int) ($row['id'] ?? 0) ?: null;
        }

        return null;
    }

    public function countByListQuery(ListUsersQueryDto $query): int
    {
        return (int) $this->listQueryBuilder($query)->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRowsByListQuery(ListUsersQueryDto $query): array
    {
        return $this->listQueryBuilder($query)
            ->order('id', 'desc')
            ->limit($query->getOffset(), $query->getPageSize())
            ->select()
            ->toArray();
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function listBriefRowsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return StaffUserEntity::query()
            ->whereIn('id', array_values($ids))
            ->field(['id', 'account', 'user_name'])
            ->select()
            ->toArray();
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function listActiveBriefRowsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return StaffUserEntity::query()
            ->whereIn('id', array_values($ids))
            ->where('status', 1)
            ->field(['id', 'account', 'user_name'])
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): StaffUserEntity
    {
        $user = new StaffUserEntity();
        $user->setData($data);
        $user->save();

        return $user;
    }

    public function save(StaffUserEntity $user): StaffUserEntity
    {
        $user->save();

        return $user;
    }

    private function listQueryBuilder(ListUsersQueryDto $query): Query
    {
        $account = trim((string) ($query->getAccount() ?? ''));
        $userName = trim((string) ($query->getUserName() ?? ''));
        $status = $query->getStatus();

        $qb = StaffUserEntity::query();
        if ($account !== '') {
            $qb->where('account', 'like', '%' . $account . '%');
        }
        if ($userName !== '') {
            $qb->where('user_name', 'like', '%' . $userName . '%');
        }
        if ($status === 1 || $status === 0) {
            $qb->where('status', $status);
        }

        return $qb;
    }
}
