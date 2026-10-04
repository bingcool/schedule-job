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
    public static function fromLogRow(array $row): ExecutionDetailDto
    {
        $dto = new ExecutionDetailDto();
        $dto->setId((int) (self::pick($row, 'id') ?? 0));
        $dto->setTaskId((int) (self::pick($row, 'cron_id', 'cronId') ?? 0));
        $dto->setExecBatchId((string) (self::pick($row, 'exec_batch_id', 'execBatchId') ?? ''));
        $dto->setPid((int) (self::pick($row, 'pid') ?? 0));
        $dto->setMessage((string) (self::pick($row, 'message') ?? ''));
        $statusCode = (int) (self::pick($row, 'status') ?? ExecutionStatus::REGISTER);
        $dto->setStatusCode($statusCode);
        $dto->setStatus(ExecutionStatus::name($statusCode));
        $dto->setTriggerType((int) (self::pick($row, 'trigger_type', 'triggerType') ?? 0));
        $rid = self::pick($row, 'request_id', 'requestId');
        $dto->setRequestId($rid !== null && $rid !== '' ? (int) $rid : null);
        $dto->setNodeId((int) (self::pick($row, 'node_id', 'nodeId') ?? 0));
        $dto->setLeaseOwner((string) (self::pick($row, 'lease_owner', 'leaseOwner') ?? ''));
        $dto->setLeaseUntil((string) (self::pick($row, 'lease_until', 'leaseUntil') ?? ''));
        $dto->setHeartbeatAt((string) (self::pick($row, 'heartbeat_at', 'heartbeatAt') ?? ''));
        $dto->setTimeoutAt((string) (self::pick($row, 'timeout_at', 'timeoutAt') ?? ''));
        $dto->setCancelledAt((string) (self::pick($row, 'cancelled_at', 'cancelledAt') ?? ''));
        $dto->setFailureReason((string) (self::pick($row, 'failure_reason', 'failureReason') ?? ''));
        $dto->setScheduledAt((string) (self::pick($row, 'scheduled_at', 'scheduledAt') ?? ''));
        $started = (string) (self::pick($row, 'started_at', 'startedAt') ?? '');
        $finished = (string) (self::pick($row, 'finished_at', 'finishedAt') ?? '');
        $created = (string) (self::pick($row, 'created_at', 'createdAt') ?? '');
        $updated = (string) (self::pick($row, 'updated_at', 'updatedAt') ?? $created);
        $dto->setStartedAt($started !== '' ? $started : $created);
        $dto->setFinishedAt($finished !== '' ? $finished : $updated);
        $dto->setDurationMs((float) (self::pick($row, 'duration_ms', 'durationMs') ?? 0));
        $exitCode = self::pick($row, 'exit_code', 'exitCode');
        $dto->setExitCode($exitCode !== null && $exitCode !== ''
            ? (int) $exitCode : null);
        $httpStatus = self::pick($row, 'http_status', 'httpStatus');
        $dto->setHttpStatus($httpStatus !== null && $httpStatus !== ''
            ? (int) $httpStatus : null);
        $taskItem = self::normalizeTaskItem(self::pick($row, 'task_item', 'taskItem'));
        $dto->setTaskItem($taskItem);
        $dto->setCommand(self::commandFromTaskItem($taskItem));
        $dto->setTaskName(self::taskNameFromRow($row, $taskItem));
        $dto->setExecType(self::execTypeFromRow($row, $taskItem));

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
