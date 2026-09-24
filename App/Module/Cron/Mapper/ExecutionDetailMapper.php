<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionDetailDto;
use Swoolefy\Worker\Cron\ExecutionStatus;

final class ExecutionDetailMapper
{
    /**
         * @param array<string, mixed> $row
         */
        public static function fromLogRow(array $row): \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionDetailDto
        {
            $dto = new \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionDetailDto();
            $dto->id = (int) (self::pick($row, 'id') ?? 0);
            $dto->taskId = (int) (self::pick($row, 'cron_id', 'cronId') ?? 0);
            $dto->execBatchId = (string) (self::pick($row, 'exec_batch_id', 'execBatchId') ?? '');
            $dto->pid = (int) (self::pick($row, 'pid') ?? 0);
            $dto->message = (string) (self::pick($row, 'message') ?? '');
            $statusCode = (int) (self::pick($row, 'status') ?? ExecutionStatus::REGISTER);
            $dto->statusCode = $statusCode;
            $dto->status = ExecutionStatus::name($statusCode);
            $dto->triggerType = (int) (self::pick($row, 'trigger_type', 'triggerType') ?? 0);
            $rid = self::pick($row, 'request_id', 'requestId');
            $dto->requestId = $rid !== null && $rid !== '' ? (int) $rid : null;
            $dto->nodeId = (int) (self::pick($row, 'node_id', 'nodeId') ?? 0);
            $dto->leaseOwner = (string) (self::pick($row, 'lease_owner', 'leaseOwner') ?? '');
            $dto->leaseUntil = (string) (self::pick($row, 'lease_until', 'leaseUntil') ?? '');
            $dto->heartbeatAt = (string) (self::pick($row, 'heartbeat_at', 'heartbeatAt') ?? '');
            $dto->timeoutAt = (string) (self::pick($row, 'timeout_at', 'timeoutAt') ?? '');
            $dto->cancelledAt = (string) (self::pick($row, 'cancelled_at', 'cancelledAt') ?? '');
            $dto->failureReason = (string) (self::pick($row, 'failure_reason', 'failureReason') ?? '');
            $dto->scheduledAt = (string) (self::pick($row, 'scheduled_at', 'scheduledAt') ?? '');
            $started = (string) (self::pick($row, 'started_at', 'startedAt') ?? '');
            $finished = (string) (self::pick($row, 'finished_at', 'finishedAt') ?? '');
            $created = (string) (self::pick($row, 'created_at', 'createdAt') ?? '');
            $updated = (string) (self::pick($row, 'updated_at', 'updatedAt') ?? $created);
            $dto->startedAt = $started !== '' ? $started : $created;
            $dto->finishedAt = $finished !== '' ? $finished : $updated;
            $dto->durationMs = (float) (self::pick($row, 'duration_ms', 'durationMs') ?? 0);
            $exitCode = self::pick($row, 'exit_code', 'exitCode');
            $dto->exitCode = $exitCode !== null && $exitCode !== ''
                ? (int) $exitCode : null;
            $httpStatus = self::pick($row, 'http_status', 'httpStatus');
            $dto->httpStatus = $httpStatus !== null && $httpStatus !== ''
                ? (int) $httpStatus : null;
            $dto->taskItem = self::normalizeTaskItem(self::pick($row, 'task_item', 'taskItem'));
            $dto->command = self::commandFromTaskItem($dto->taskItem);
            $dto->taskName = self::taskNameFromRow($row, $dto->taskItem);
            $dto->execType = self::execTypeFromRow($row, $dto->taskItem);

            return $dto;
        }

    /**
         * @param array<string, mixed> $item
         */
        private static function commandFromTaskItem(array $item): string
        {
            foreach (['command', 'exec_script', 'url'] as $key) {
                $value = trim((string) ($item[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        }

    /**
         * @param array<string, mixed> $row
         * @param array<string, mixed> $item
         */
        private static function taskNameFromRow(array $row, array $item): string
        {
            foreach (['cron_name', 'name', 'task_name'] as $key) {
                $value = trim((string) ($item[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
            foreach (['task_name', 'taskName', 'cron_name'] as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        }

    /**
         * @param array<string, mixed> $row
         * @param array<string, mixed> $item
         */
        private static function execTypeFromRow(array $row, array $item): int
        {
            $execType = (int) (self::pick($row, 'exec_type', 'execType') ?? 0);
            if ($execType > 0) {
                return $execType;
            }

            return (int) ($item['exec_type'] ?? $item['execType'] ?? 0);
        }

    /**
         * @param array<string, mixed> $row
         */
        private static function pick(array $row, string $snake, ?string $camel = null): mixed
        {
            if (array_key_exists($snake, $row)) {
                return $row[$snake];
            }
            if ($camel !== null && array_key_exists($camel, $row)) {
                return $row[$camel];
            }

            return null;
        }

    /**
         * @return array<string, mixed>
         */
        private static function normalizeTaskItem(mixed $value): array
        {
            if ($value === null || $value === '') {
                return [];
            }
            if (is_array($value)) {
                return $value;
            }
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }

                return ['raw' => $value];
            }

            return ['raw' => (string) $value];
        }
}
