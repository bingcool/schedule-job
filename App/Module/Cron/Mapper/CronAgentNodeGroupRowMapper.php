<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeGroupRowDto;

final class CronAgentNodeGroupRowMapper
{
    /**
         * @param array<string, mixed> $row cron_agent_node_group 查询行或实体 getAttributes() 结果
         */
        public static function fromEntityRow(array $row): CronAgentNodeGroupRowDto
        {
            $dto = new CronAgentNodeGroupRowDto();
            $id = (int)($row['id'] ?? 0);
            $dto->setId($id);
            $dto->setGroupId($id);
            $dto->setGroupName((string)($row['group_name'] ?? ''));
            $dto->setRemark((string)($row['remark'] ?? ''));
            $dto->setRobotId((int)($row['robot_id'] ?? 0));
            $dto->setRobotName((string)($row['robot_name'] ?? ''));
            $dto->setRobotPlatform((int)($row['robot_platform'] ?? 0));
            $dto->setRobotStatus((int)($row['robot_status'] ?? 0));
            $dto->setNodeCount((int)($row['node_count'] ?? 0));
            $dto->setCreatedAt((string)($row['created_at'] ?? ''));
            $dto->setUpdatedAt((string)($row['updated_at'] ?? ''));

            return $dto;
        }
}
