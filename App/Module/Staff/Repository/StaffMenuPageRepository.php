<?php

declare(strict_types=1);

namespace App\Module\Staff\Repository;

use App\Module\Staff\Entity\StaffMenuPageEntity;
use App\Module\Staff\StaffApp;
use Swoolefy\Library\Db\Query;

class StaffMenuPageRepository
{
    public function findById(int $id): ?StaffMenuPageEntity
    {
        if ($id <= 0) {
            return null;
        }

        return (new StaffMenuPageEntity())->loadById($id);
    }

    public function findVisibleByCode(string $code): ?StaffMenuPageEntity
    {
        $row = StaffMenuPageEntity::queryVisible()->where('code', $code)->find();
        if (!$row) {
            return null;
        }

        return $this->findById((int) $row['id']);
    }

    public function hasAnyForApp(): bool
    {
        return (bool) StaffMenuPageEntity::queryVisible()->where('app_id', StaffApp::appId())->find();
    }

    /**
     * @param array<int, int> $pageIds
     * @return array<int, array<string, mixed>>
     */
    public function listVisibleRowsByIds(array $pageIds): array
    {
        if ($pageIds === []) {
            return [];
        }

        return StaffMenuPageEntity::queryVisible()
            ->where('app_id', StaffApp::appId())
            ->whereIn('id', $pageIds)
            ->select()
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listVisibleRows(?int $status = null): array
    {
        $qb = StaffMenuPageEntity::queryVisible()->where('app_id', StaffApp::appId());
        if ($status !== null) {
            $qb->where('status', $status);
        }

        return $qb->order('sort', 'desc')->order('id', 'asc')->select()->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSiblingRows(int $parentId): array
    {
        return StaffMenuPageEntity::queryVisible()
            ->where('app_id', StaffApp::appId())
            ->where('parent_id', $parentId)
            ->select()
            ->toArray();
    }

    public function hasVisibleChild(int $menuId): bool
    {
        return (bool) StaffMenuPageEntity::queryVisible()->where('parent_id', $menuId)->find();
    }

    public function existsVisibleCode(string $code, int $exceptId = 0): bool
    {
        $qb = StaffMenuPageEntity::queryVisible()->where('code', $code);
        if ($exceptId > 0) {
            $qb->where('id', '<>', $exceptId);
        }

        return (bool) $qb->find();
    }

    public function existsVisibleUriForApp(string $uri, int $exceptId = 0): bool
    {
        $qb = StaffMenuPageEntity::queryVisible()->where('uri', $uri)->where('app_id', StaffApp::appId());
        if ($exceptId > 0) {
            $qb->where('id', '<>', $exceptId);
        }

        return (bool) $qb->find();
    }

    /**
     * @param array<int, int> $pageIds
     * @return array<int, array<string, mixed>>
     */
    public function listEnabledVisibleRowsByIds(array $pageIds): array
    {
        if ($pageIds === []) {
            return [];
        }

        return StaffMenuPageEntity::queryVisible()
            ->where('app_id', StaffApp::appId())
            ->whereIn('id', $pageIds)
            ->where('status', StaffApp::MENU_STATUS_ENABLED)
            ->select()
            ->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): StaffMenuPageEntity
    {
        $menu = new StaffMenuPageEntity();
        $menu->setData($data);
        $menu->save();

        return $menu;
    }

    public function save(StaffMenuPageEntity $menu): StaffMenuPageEntity
    {
        $menu->save();

        return $menu;
    }
}
