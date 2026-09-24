<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskOperationLogRowDto;
use App\Module\Cron\CronTaskOperationType;

final class CronTaskOperationLogRowMapper
{
    /**
         * @param array<string, mixed> $row
         */
        public static function fromEntityRow(array $row): \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskOperationLogRowDto
        {
            $dto = new \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskOperationLogRowDto();
            $actionType = (int) ($row['action_type'] ?? $row['actionType'] ?? 0);
            $dto->setId((int) ($row['id'] ?? 0));
            $dto->setCronId((int) ($row['cron_id'] ?? $row['cronId'] ?? 0));
            $dto->setTaskName((string) ($row['task_name'] ?? $row['taskName'] ?? ''));
            $dto->setActionType($actionType);
            $dto->setActionTypeName(CronTaskOperationType::label($actionType));
            $dto->setOperatorId((int) ($row['operator_id'] ?? $row['operatorId'] ?? 0));
            $dto->setOperatorName((string) ($row['operator_name'] ?? $row['operatorName'] ?? ''));
            $before = $row['content_before'] ?? $row['contentBefore'] ?? null;
            $after = $row['content_after'] ?? $row['contentAfter'] ?? null;
            $dto->setContentBefore(self::decodeJsonField($before));
            $dto->setContentAfter(self::decodeJsonField($after));
            $dto->setCreatedAt((string) ($row['created_at'] ?? $row['createdAt'] ?? ''));

            return $dto;
        }

    /**
         * @return array<string, mixed>|null
         */
        private static function decodeJsonField(mixed $value): ?array
        {
            if (is_array($value)) {
                return $value;
            }
            if (!is_string($value) || trim($value) === '') {
                return null;
            }
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }
}
