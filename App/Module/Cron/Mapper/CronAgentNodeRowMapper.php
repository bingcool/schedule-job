<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto;
use Swoolefy\Worker\Cron\CronNodeLiveness;

final class CronAgentNodeRowMapper
{
    /**
         * 从数据库实体行（snake_case）映射为 DTO。
         *
         * @param array<string, mixed> $row cron_agent_node 查询行或实体 getAttributes() 结果
         */
        public static function fromEntityRow(array $row): \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto
        {
            $dto = new \InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto();
            $dto->setId((int)($row['id'] ?? 0));
            $dto->setNodeName((string)($row['node_name'] ?? ''));
            $dto->setNodeIp((string)($row['node_ip'] ?? ''));
            $dto->setGroupId((int)($row['group_id'] ?? 0));
            $dto->setGroupName((string)($row['group_name'] ?? ''));
            $dto->setRemark((string)($row['remark'] ?? ''));
            $lastHb = (string)($row['last_heartbeat_at'] ?? '');
            $interval = CronNodeLiveness::normalizeInterval((int)($row['heartbeat_interval'] ?? 0));
            $now = time();
            $dto->setLastHeartbeatAt($lastHb);
            $dto->setHeartbeatInterval($interval);
            $dto->setStaleAfterSeconds(CronNodeLiveness::staleAfterSeconds($interval));
            $dto->setStatus(self::deriveHeartbeatStatus($lastHb, $now, $interval));
            $dto->setTaskCount((int)($row['task_count'] ?? 0));
            $dto->setCreatedAt((string)($row['created_at'] ?? ''));
            $dto->setUpdatedAt((string)($row['updated_at'] ?? ''));

            return $dto;
        }

    /**
         * 按该节点自己的心跳间隔判定 online / offline。
         * 从未心跳或无法解析时间为 offline，不返回 unknown。
         *
         * @param string $lastHeartbeatAt DB datetime 或 unix 秒字符串
         * @param int|null $now 当前 unix 秒，缺省 time()
         * @param int $interval 该节点 heartbeat_interval（秒）
         */
        public static function deriveHeartbeatStatus(
            string $lastHeartbeatAt,
            ?int $now = null,
            int $interval = CronNodeLiveness::DEFAULT_INTERVAL,
        ): string {
            return CronNodeLiveness::status(
                $now ?? time(),
                CronNodeLiveness::parseHeartbeatAt($lastHeartbeatAt),
                $interval,
            );
        }
}
