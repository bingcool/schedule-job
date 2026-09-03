CREATE TABLE `cron_task_operation_log` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `cron_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'cron_task.id',
    `node_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作时的节点ID快照',
    `task_name` varchar(128) NOT NULL DEFAULT '' COMMENT '操作时的任务名称快照',
    `action_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1=启用 2=禁用 3=删除 4=执行 5=编辑',
    `operator_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人 staff_user.id',
    `operator_name` varchar(128) NOT NULL DEFAULT '' COMMENT '操作人展示名快照',
    `content_before` json DEFAULT NULL COMMENT '变更前任务内容（编辑/删除）',
    `content_after` json DEFAULT NULL COMMENT '变更后任务内容（编辑/启用/禁用/执行）',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
    PRIMARY KEY (`id`),
    KEY `idx_cron_id` (`cron_id`),
    KEY `idx_node_id` (`node_id`),
    KEY `idx_task_name` (`task_name`),
    KEY `idx_action_type` (`action_type`),
    KEY `idx_operator_id` (`operator_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='计划任务操作审计日志';
