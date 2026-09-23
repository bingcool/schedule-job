<?php

declare(strict_types=1);

namespace App\Module\Cron\Repository;

use App\Module\Cron\Entity\CronRobotAlertLogEntity;

class CronRobotAlertLogRepository
{
    /**
     * @param array<string, mixed> $row
     */
    public function insert(array $row): void
    {
        CronRobotAlertLogEntity::query()->insert($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertIgnoringDuplicateKey(array $row): void
    {
        try {
            $this->insert($row);
        } catch (\Throwable $e) {
            if ($this->isDuplicateKey($e)) {
                return;
            }
            throw $e;
        }
    }

    public function isDuplicateKey(\Throwable $e): bool
    {
        $code = $e->getCode();
        if ($code === 23000 || $code === '23000' || (int) $code === 1062) {
            return true;
        }
        $msg = $e->getMessage();
        if (str_contains($msg, '1062') || str_contains($msg, 'uk_execution_id') || str_contains($msg, 'Duplicate')) {
            return true;
        }
        $prev = $e->getPrevious();
        if ($prev instanceof \PDOException) {
            return (int) ($prev->errorInfo[1] ?? 0) === 1062;
        }

        return false;
    }
}
