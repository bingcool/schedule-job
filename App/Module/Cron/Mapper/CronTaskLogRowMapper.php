<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskLogRowDto;
use Swoolefy\Worker\Cron\ExecutionStatus;

final class CronTaskLogRowMapper
{
    /**
         * 从数据库实体行（snake_case）映射为 DTO。
         *
         * taskItem 列：数组原样保留；非数组非空值包装为 `['raw' => ...]`；空值置 null。
         *
         * @param array<string, mixed> $row cron_task_log 查询行
         */
        public static function fromEntityRow(array $row): \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskLogRowDto
        {
            $dto = new \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskLogRowDto();
            $dto->setId((int)($row['id'] ?? 0));
            $dto->setCronId((int)($row['cron_id'] ?? 0));
            $dto->setTaskName((string)($row['task_name'] ?? $row['cron_name'] ?? ''));
            $dto->setExecBatchId((string)($row['exec_batch_id'] ?? ''));
            $dto->setPid((int)($row['pid'] ?? 0));
            $status = (int)($row['status'] ?? ExecutionStatus::REGISTER);
            $dto->setStatus($status);
            $dto->setStatusName(ExecutionStatus::name($status));
            $dto->setTriggerType((int)($row['trigger_type'] ?? 0));
            $dto->setExecType(self::resolveExecType($row));
            $dto->setTaskStatus(self::resolveTaskStatus($row));
            $rid = $row['request_id'] ?? null;
            $dto->requestId = $rid !== null && $rid !== '' ? (int) $rid : null;
            $dto->nodeId = (int) ($row['node_id'] ?? 0);
            $dto->leaseOwner = (string) ($row['lease_owner'] ?? '');
            $dto->leaseUntil = (string) ($row['lease_until'] ?? '');
            $dto->heartbeatAt = (string) ($row['heartbeat_at'] ?? '');
            $dto->timeoutAt = (string) ($row['timeout_at'] ?? '');
            $dto->cancelledAt = (string) ($row['cancelled_at'] ?? '');
            $dto->failureReason = (string) ($row['failure_reason'] ?? '');
            $dto->setScheduledAt((string)($row['scheduled_at'] ?? ''));
            $dto->setStartedAt((string)($row['started_at'] ?? ''));
            $dto->setFinishedAt((string)($row['finished_at'] ?? ''));
            $dto->setDurationMs((int)($row['duration_ms'] ?? 0));
            $dto->setExitCode(isset($row['exit_code']) && $row['exit_code'] !== null && $row['exit_code'] !== '' ? (int)$row['exit_code'] : null);
            $dto->setHttpStatus(isset($row['http_status']) && $row['http_status'] !== null && $row['http_status'] !== '' ? (int)$row['http_status'] : null);
            $dto->setTaskItem(self::normalizeTaskItem(self::pick($row, 'task_item', 'taskItem')));
            $dto->setMessage((string)($row['message'] ?? ''));
            $dto->setCreatedAt((string)($row['created_at'] ?? ''));
            $dto->setUpdatedAt((string)($row['updated_at'] ?? ''));

            return $dto;
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
         * 优先用关联任务的 exec_type；任务已删时回落到执行快照。
         *
         * @param array<string, mixed> $row
         */
        private static function resolveExecType(array $row): int
        {
            $execType = (int) ($row['exec_type'] ?? $row['execType'] ?? 0);
            if ($execType > 0) {
                return $execType;
            }
            $item = self::normalizeTaskItem(self::pick($row, 'task_item', 'taskItem'));
            if ($item === null) {
                return 0;
            }

            return (int) ($item['exec_type'] ?? $item['execType'] ?? 0);
        }

    /**
         * 配置变更当时的任务启停。优先读 task_item.status，旧日志回落到 message 文案。
         *
         * @param array<string, mixed> $row
         */
        private static function resolveTaskStatus(array $row): ?int
        {
            $item = self::normalizeTaskItem(self::pick($row, 'task_item', 'taskItem'));
            if (is_array($item) && array_key_exists('status', $item) && $item['status'] !== '' && $item['status'] !== null) {
                return (int) $item['status'] === 1 ? 1 : 0;
            }
            $message = (string) ($row['message'] ?? '');
            if (str_contains($message, '【启用】') || str_contains($message, 'ENABLE')) {
                return 1;
            }
            if (str_contains($message, '【禁用】') || str_contains($message, 'DISABLE')) {
                return 0;
            }

            return null;
        }

    /**
         * @return array<string, mixed>|null
         */
        private static function normalizeTaskItem(mixed $value): ?array
        {
            if ($value === null || $value === '') {
                return null;
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
