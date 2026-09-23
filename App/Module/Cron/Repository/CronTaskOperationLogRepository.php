<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronTaskOperationLogEntity;
use Swoolefy\Library\Db\Query;

class CronTaskOperationLogRepository
{
    public function newQuery(): Query
    {
        return CronTaskOperationLogEntity::query();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insert(array $row): void
    {
        CronTaskOperationLogEntity::query()->insert($row);
    }
}
