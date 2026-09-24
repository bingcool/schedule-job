<?php

declare(strict_types=1);

namespace App\Module\Cron\Assembler;

use App\Module\Cron\Mapper\CronAgentNodeGroupRowMapper;
use App\Module\Cron\Mapper\CronAgentNodeRowMapper;
use App\Module\Cron\Mapper\CronRobotRowMapper;
use App\Module\Cron\Mapper\CronTaskLogRowMapper;
use App\Module\Cron\Mapper\CronTaskOperationLogRowMapper;
use App\Module\Cron\Mapper\CronTaskRowMapper;
use App\Module\Cron\Mapper\CronTaskStatsResultMapper;
use App\Module\Cron\Mapper\ExecutionDetailMapper;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotRowDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeGroupRowDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskLogRowDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskOperationLogRowDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskRowDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskStatsResultDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\ExecutionDetailDto;

/** Service 层组装 InterfaceApi 契约 DTO（映射逻辑不在 Dto 内）。 */
final class CronContractDtoAssembler
{
    /**
     * @param array<string, mixed> $row
     */
    public static function robotRowFromEntityRow(array $row): CronRobotRowDto
    {
        return CronRobotRowMapper::fromEntityRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function agentNodeRowFromEntityRow(array $row): CronAgentNodeRowDto
    {
        return CronAgentNodeRowMapper::fromEntityRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function agentNodeGroupRowFromEntityRow(array $row): CronAgentNodeGroupRowDto
    {
        return CronAgentNodeGroupRowMapper::fromEntityRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function taskRowFromEntityRow(array $row, ?int $now = null): CronTaskRowDto
    {
        return CronTaskRowMapper::fromEntityRow($row, $now);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function taskLogRowFromEntityRow(array $row): CronTaskLogRowDto
    {
        return CronTaskLogRowMapper::fromEntityRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function taskOperationLogRowFromEntityRow(array $row): CronTaskOperationLogRowDto
    {
        return CronTaskOperationLogRowMapper::fromEntityRow($row);
    }

    /**
     * @param array<string, mixed> $stats
     */
    public static function taskStatsFromAggregated(int $taskId, array $stats): CronTaskStatsResultDto
    {
        return CronTaskStatsResultMapper::fromAggregated($taskId, $stats);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function executionDetailFromLogRow(array $row): ExecutionDetailDto
    {
        return ExecutionDetailMapper::fromLogRow($row);
    }
}
