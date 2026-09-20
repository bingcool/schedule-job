CREATE TABLE `cron_task` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `node_id` int unsigned NOT NULL DEFAULT '1' COMMENT '节点ID',
    `cron_name` varchar(128) NOT NULL DEFAULT '' COMMENT '任务名称（API 字段 name）',
    `expression` varchar(128) NOT NULL DEFAULT '' COMMENT 'cron表达式',
    `command` varchar(256) NOT NULL DEFAULT '' COMMENT '执行命令',
    `exec_type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '执行类型 1-GLUE(shell)，2-http，3-kubernetes',
    `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态 0-禁用，1-启用',
    `with_block_lapping` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否阻塞执行 0-否，1->是',
    `retry` int NOT NULL DEFAULT '0' COMMENT '失败后重试次数（不含首次；0=不重试，N=最多再试N次）',
    `timeout` int NOT NULL DEFAULT '0' COMMENT 'Shell执行超时秒数，0=不限制；HTTP仍用 http_request_time_out',
    `description` varchar(256) NOT NULL DEFAULT '' COMMENT '描述',
    `cron_between` json DEFAULT NULL COMMENT '允许执行时间段',
    `cron_skip` json DEFAULT NULL COMMENT '不允许执行时间段(即需跳过的时间段)',
    `http_method` varchar(16) NOT NULL DEFAULT '' COMMENT 'http请求方法',
    `http_body` json DEFAULT NULL COMMENT 'http请求体',
    `http_headers` json DEFAULT NULL COMMENT 'http请求头',
    `http_request_time_out` int NOT NULL DEFAULT '0' COMMENT 'http请求超时时间，单位：秒',
    `k8s_spec` json DEFAULT NULL COMMENT 'exec_type=3：{namespace,deployment,container,command[],args[]}；不存image，执行时现取Deployment模板',
    `created_by` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人 staff_user.id',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_cron_name` (`cron_name`),
    KEY `node_id` (`node_id`),
    KEY `expression` (`expression`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='定时任务表';

-- 插入数据
INSERT INTO `cron_task` (`id`, `cron_name`, `expression`, `command`, `exec_type`, `status`, `with_block_lapping`, `description`, `cron_between`, `cron_skip`, `http_method`, `http_body`, `http_headers`, `http_request_time_out`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'shell-demo', '15', 'ls -l', 1, 1, 0, '', NULL, NULL, '', NULL, NULL, 0, '2025-04-24 19:12:34', '2025-04-27 19:10:20', NULL);
INSERT INTO `cron_task` (`id`, `cron_name`, `expression`, `command`, `exec_type`, `status`, `with_block_lapping`, `description`, `cron_between`, `cron_skip`, `http_method`, `http_body`, `http_headers`, `http_request_time_out`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'http-demo', '20', 'https://www.baidu.com', 2, 1, 0, '334', NULL, NULL, 'GET', NULL, NULL, 0, '2025-04-25 16:18:10', '2025-04-27 19:55:22', NULL);


CREATE TABLE `cron_robot` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '机器人名称，未删除范围内应用层唯一',
    `platform` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1-企业微信wecom 2-钉钉dingtalk 3-飞书feishu',
    `webhook_url` varchar(2048) NOT NULL DEFAULT '' COMMENT 'Webhook 完整地址（含 key），敏感，API 必须脱敏',
    `secret` varchar(512) NOT NULL DEFAULT '' COMMENT '签名密钥，可空（企微通常不需要）；API 不回明文',
    `config_json` json DEFAULT NULL COMMENT '平台扩展配置',
    `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '0-禁用 1-启用',
    `last_test_at` datetime DEFAULT NULL COMMENT '最近一次点「测试」的时间',
    `last_test_ok` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '最近测试是否成功：0-否/未测 1-是',
    `last_test_error` varchar(1000) NOT NULL DEFAULT '' COMMENT '最近测试失败原因，成功则清空',
    `created_by` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人 staff_user.id',
    `updated_by` int unsigned NOT NULL DEFAULT '0' COMMENT '最后修改人 staff_user.id',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '软删时间',
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`),
    KEY `idx_platform_status` (`platform`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron群机器';


CREATE TABLE `cron_agent_node_group` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `group_name` varchar(128) NOT NULL DEFAULT '' COMMENT '分组名称（唯一）',
    `robot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '绑定的 cron_robot.id；0=该组不发告警',
    `remark` varchar(256) NOT NULL DEFAULT '' COMMENT '备注',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_group_name` (`group_name`),
    KEY `idx_robot_id` (`robot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron Agent 节点分组';


CREATE TABLE `cron_agent_node` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `group_id` int unsigned DEFAULT NULL COMMENT '所属分组ID（cron_agent_node_group.id）；历史数据可空',
    `node_name` varchar(128) NOT NULL DEFAULT '' COMMENT '节点名称',
    `node_ip` varchar(64) NOT NULL DEFAULT '' COMMENT '节点IP',
    `api_key` varchar(64) NOT NULL DEFAULT '' COMMENT 'api_key',
    `remark` varchar(256) NOT NULL DEFAULT '' COMMENT '备注',
    `last_heartbeat_at` datetime DEFAULT NULL COMMENT '最近一次 Agent 心跳时间',
    `heartbeat_interval` int unsigned NOT NULL DEFAULT '15' COMMENT '该节点心跳间隔（秒）；Ack 时由 Worker 写入，Admin 按节点自身间隔判定存活',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_group_id` (`group_id`),
    KEY `idx_last_heartbeat` (`last_heartbeat_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron Agent 节点';


CREATE TABLE `cron_task_run_request` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `cron_id` bigint NOT NULL DEFAULT '0' COMMENT '关联 cron_task.id',
    `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入队时间',
    `consumed_at` datetime DEFAULT NULL COMMENT 'Cron Worker 消费时间；NULL=待执行',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_cron_pending` (`cron_id`, `consumed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手动执行请求（跨进程 runOnceNow 入队）';

CREATE TABLE `cron_task_log` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `cron_id` bigint NOT NULL DEFAULT '0' COMMENT '关联的cron_task.id',
    `exec_batch_id` varchar(64) NOT NULL DEFAULT '' COMMENT '每轮执行的批次id',
    `pid` int NOT NULL DEFAULT '0' COMMENT '定时脚本执行时的进程pid',
    `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '执行状态：0-register 1-running 2-success 3-failed 4-skipped 5-timeout 6-cancelled 7-unregister 8-cancel_requested',
    `trigger_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '触发类型：1-scheduler 2-run_once',
    `request_id` bigint unsigned DEFAULT NULL COMMENT '关联 cron_task_run_request.id；调度触发为空，手动执行为请求主键',
    `node_id` int unsigned NOT NULL DEFAULT '0' COMMENT '执行节点快照（cron_agent_node.id）；Recovery 按节点收敛，禁止异节点重跑',
    `lease_owner` varchar(128) NOT NULL DEFAULT '' COMMENT 'Execution Lease owner，格式 node_id:worker_pid:boot_id',
    `lease_until` datetime DEFAULT NULL COMMENT 'Lease 过期时间；status=RUNNING 且已过期表示 Worker 可能已崩溃',
    `heartbeat_at` datetime DEFAULT NULL COMMENT 'Execution 心跳时间；执行期内由持有 Lease 的 Worker 续租写入',
    `timeout_at` datetime DEFAULT NULL COMMENT 'Execution 超时截止时间；started_at+timeout，仅 Shell 到期杀进程',
    `cancelled_at` datetime DEFAULT NULL COMMENT 'Cancel 请求时间；RUNNING→cancel_requested 时写入',
    `failure_reason` varchar(64) NOT NULL DEFAULT '' COMMENT '失败原因：WORKER_CRASH/LEASE_EXPIRED/TIMEOUT/CANCELLED/PROCESS_EXIT_ERROR/EXECUTION_ERROR',
    `scheduled_at` datetime DEFAULT NULL COMMENT '计划执行时间',
    `started_at` datetime DEFAULT NULL COMMENT '实际开始执行时间',
    `finished_at` datetime DEFAULT NULL COMMENT '实际结束执行时间',
    `duration_ms` bigint unsigned NOT NULL DEFAULT '0' COMMENT '执行耗时，毫秒',
    `exit_code` int DEFAULT NULL COMMENT 'Shell退出码',
    `http_status` smallint unsigned DEFAULT NULL COMMENT 'HTTP响应状态码',
    `task_item` text DEFAULT NULL COMMENT '执行任务项meta信息',
    `message` text DEFAULT NULL COMMENT '运行态记录信息（人类可读，禁止用于 taskStats）',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `expression` (`exec_batch_id`),
    KEY `idx_cron_id_created_at` (`cron_id`, `created_at`),
    KEY `idx_cron_status_created_at` (`cron_id`, `status`, `created_at`),
    KEY `idx_status_created_at` (`status`, `created_at`),
    KEY `idx_cron_exec_batch` (`cron_id`, `exec_batch_id`),
    UNIQUE KEY `uk_request_id` (`request_id`) COMMENT 'RunOnce request_id 原子 Claim；普通 Cron 为 NULL',
    KEY `idx_lease_running` (`status`, `lease_until`) COMMENT '扫描过期 RUNNING/cancel_requested 做 Crash Recovery',
    KEY `idx_node_status` (`node_id`, `status`) COMMENT '按节点过滤进行中/过期 Execution'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='定时任务执行记录（Execution Record）';

CREATE TABLE `cron_scheduled_task_record` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `cron_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT 'cron_task.id',
    `scheduled_at` datetime NOT NULL COMMENT 'Cron Slot，等于 CronManager plannedAt',
    `execution_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '关联 cron_task_log.id',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cron_scheduled_at` (`cron_id`, `scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron调度Slot占位';

CREATE TABLE `cron_task_operation_log` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `cron_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'cron_task.id',
    `node_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作时的节点ID快照',
    `task_name` varchar(128) NOT NULL DEFAULT '' COMMENT '操作时的任务名称快照',
    `action_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1=启用 2=禁用 3=删除 4=执行 5=编辑 6=取消执行',
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

CREATE TABLE `cron_robot_alert_log` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `execution_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT 'cron_task_log.id',
    `cron_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT 'cron_task.id',
    `exec_batch_id` varchar(64) NOT NULL DEFAULT '' COMMENT '执行批次，与 cron_task_log.exec_batch_id 一致',
    `robot_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '发送时使用的机器人',
    `platform` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '发送时平台快照：1-wecom 2-dingtalk 3-feishu',
    `alert_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1-FAILED 2-TIMEOUT',
    `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1-发送成功 2-发送失败 3-跳过（机器人禁用/已删）',
    `skip_reason` varchar(32) NOT NULL DEFAULT '' COMMENT '跳过原因：robot_disabled/robot_not_found；成功或失败为空',
    `http_status` smallint NOT NULL DEFAULT 0 COMMENT 'Webhook HTTP 状态码，未发出为 0',
    `error_message` varchar(1000) NOT NULL DEFAULT '' COMMENT '失败/跳过原因说明，禁止写入 webhook URL 或 secret',
    `sent_at` datetime DEFAULT NULL COMMENT 'Webhook 调用结束时间（成功或失败都写)',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_execution_id` (`execution_id`),
    KEY `idx_robot_created` (`robot_id`, `created_at`),
    KEY `idx_cron_created` (`cron_id`, `created_at`),
    KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron机器人告警投递记录';



-- Schedule Job RBAC schema
-- staff_user.status：1-启用，0-禁用；delete_at：删除时间

CREATE TABLE IF NOT EXISTS `staff_user` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增用户ID',
    `account` varchar(128) NOT NULL DEFAULT '' COMMENT '用户账号(建议邮箱)',
    `email` varchar(128) NOT NULL DEFAULT '' COMMENT '登录邮箱',
    `password` varchar(128) NOT NULL DEFAULT '' COMMENT '密码哈希',
    `user_name` varchar(128) NOT NULL DEFAULT '' COMMENT '用户名称',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-启用,0-禁用',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `enabled_at` datetime DEFAULT NULL COMMENT '启用时间（新增或再次启用）',
    `disabled_at` datetime DEFAULT NULL COMMENT '启用后，再次禁用的时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `account` (`account`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录的用户表';

CREATE TABLE IF NOT EXISTS `staff_user_relate_node_group` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `user_id` int unsigned NOT NULL COMMENT '用户ID',
    `node_group_id` bigint unsigned NOT NULL COMMENT '节点组ID',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除|禁用时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户拥有的Cron节点组';

CREATE TABLE IF NOT EXISTS `staff_menu_pages` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `name` varchar(128) NOT NULL DEFAULT '' COMMENT '页面节点名称',
    `parent_prefix` varchar(256) NOT NULL DEFAULT '' COMMENT '父页面所有id',
    `parent_id` int DEFAULT '0' COMMENT '父页面Id',
    `uri` varchar(256) NOT NULL DEFAULT '' COMMENT '页面URI',
    `code` varchar(256) NOT NULL DEFAULT '' COMMENT '唯一标志',
    `icon` varchar(256) NOT NULL DEFAULT '' COMMENT '图标',
    `sort` int unsigned DEFAULT '0' COMMENT '排序：越大越靠前',
    `status` tinyint(2) unsigned DEFAULT '1' COMMENT '状态：0-禁用(菜单栏不展示)，1-启用，2-删除',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除|禁用时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_uri_appid` (`uri`,`app_id`),
    UNIQUE KEY `uniq_code` (`code`),
    KEY `idx_pid` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='菜单页面节点表';

CREATE TABLE IF NOT EXISTS `staff_role_page` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `role_id` bigint unsigned NOT NULL COMMENT '角色Id',
    `page_id` bigint unsigned NOT NULL COMMENT '页面Id',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq-key` (`app_id`,`role_id`,`page_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色-页面权限表';

CREATE TABLE IF NOT EXISTS `staff_role_permission` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `type` tinyint(2) unsigned NOT NULL COMMENT '类型1-api接口，2-关联任务接口',
    `role_id` bigint unsigned NOT NULL COMMENT '角色Id',
    `per_id` bigint unsigned NOT NULL COMMENT '权限Id',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_uk-key` (`app_id`,`type`,`role_id`,`per_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色-api权限表';

CREATE TABLE IF NOT EXISTS `staff_roles` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint NOT NULL DEFAULT '0' COMMENT '应用id',
    `is_super_role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是超级管理员角色组',
    `name` varchar(64) NOT NULL DEFAULT '' COMMENT '角色名称',
    `code` varchar(128) NOT NULL DEFAULT '' COMMENT '唯一标识',
    `desc` varchar(256) NOT NULL DEFAULT '' COMMENT '角色描述',
    `status` tinyint(1) DEFAULT '1' COMMENT '状态：0-禁用，1-启用',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

CREATE TABLE IF NOT EXISTS `staff_user_role` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `user_id` bigint unsigned NOT NULL COMMENT '用户Id',
    `role_id` bigint unsigned NOT NULL COMMENT '角色Id',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq-userid-roleid-appid` (`user_id`,`role_id`,`app_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户与角色关联表';

-- 初始化插入菜单栏
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (1, 1, 'Dashboard', '12', 12, '/dashboard', 'cron:dashboard', 'el-icon-data-line', 100, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (2, 1, '计划任务', '12', 12, '/tasks', 'cron:tasks', 'el-icon-s-order', 90, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (4, 1, '执行记录', '12', 12, '/executions', 'cron:executions', 'el-icon-time', 80, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (6, 1, 'Cron Nodes', '12', 12, '/nodes', 'cron:nodes', 'el-icon-monitor', 70, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (8, 1, '权限管理', '', 0, '/auth', 'auth', 'el-icon-lock', 50, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (9, 1, '用户管理', '8', 8, '/users', 'auth:users', 'el-icon-user', 0, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (10, 1, '角色管理', '8', 8, '/roles', 'auth:roles', 'el-icon-s-custom', 0, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (11, 1, '菜单管理', '8', 8, '/menus', 'auth:menus', 'el-icon-menu', 0, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (12, 1, 'Cron 管理', '', 0, '/cron', 'cron', '', 100, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (13, 1, '系统设置', '', 0, '/system', 'system', 'el-icon-setting', 40, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (14, 1, '机器人告警', '13', 13, '/robots', 'system:robots', 'el-icon-bell', 0, 1);


-- 系统默认角色：初始化插入超级管理员角色和编辑任务角色组
INSERT INTO staff_roles (id, app_id, is_super_role, name, code, `desc`, status) VALUES (1, 1, 1, '超级管理员', 'super_admin', '拥有系统全部权限', 1);
INSERT INTO staff_roles (id, app_id, is_super_role, name, code, `desc`, status) VALUES (2, 1, 0, '编辑任务角色组', 'editer_task_group', '登录的用户拥有该角色时，可以编辑不属于他创建的计划任务', 1);

-- 初始化超级管理员用户（账号 admin，默认密码 123456789，部署后请立即修改）
INSERT INTO staff_user (id, account, password, user_name, status) VALUES (1, 'admin', '$2y$12$E5H2YsF9n1VnwoUsxq9Md.TzOCVLe6wUAksNmvYoNsN.N00l54fLS', '超级管理员', 1);

-- 初始化超级管理员分配超管角色
INSERT INTO staff_user_role (app_id, user_id, role_id) VALUES (1, 1, 1);

