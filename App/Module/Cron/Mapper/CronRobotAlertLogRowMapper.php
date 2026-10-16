<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use App\Module\Cron\RobotAlertDeliveryStatus;
use App\Module\Cron\RobotAlertType;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotAlertLogRowDto;

final class CronRobotAlertLogRowMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromEntityRow(array $row): CronRobotAlertLogRowDto
    {
        $alertType = (int) ($row['alert_type'] ?? 0);
        $status = (int) ($row['status'] ?? 0);
        $dto = new CronRobotAlertLogRowDto();
        $dto->setId((int) ($row['id'] ?? 0));
        $dto->setRobotId((int) ($row['robot_id'] ?? 0));
        $dto->setRobotName((string) ($row['robot_name'] ?? ''));
        $dto->setPlatform((int) ($row['platform'] ?? 0));
        $dto->setAlertType($alertType);
        $dto->setAlertTypeName(RobotAlertType::name($alertType));
        $dto->setStatus($status);
        $dto->setStatusName(RobotAlertDeliveryStatus::name($status));
        $dto->setSkipReason((string) ($row['skip_reason'] ?? ''));
        $dto->setHttpStatus((int) ($row['http_status'] ?? 0));
        $dto->setErrorMessage((string) ($row['error_message'] ?? ''));
        $dto->setSentAt((string) ($row['sent_at'] ?? ''));
        $dto->setCronId((int) ($row['cron_id'] ?? 0));
        $dto->setTaskName((string) ($row['task_name'] ?? ''));
        $dto->setExecutionId((int) ($row['execution_id'] ?? 0));
        $dto->setNodeId((int) ($row['node_id'] ?? 0));
        $dto->setNodeName((string) ($row['node_name'] ?? ''));
        $dto->setExecBatchId((string) ($row['exec_batch_id'] ?? ''));
        $dto->setCreatedAt((string) ($row['created_at'] ?? ''));

        return $dto;
    }
}
