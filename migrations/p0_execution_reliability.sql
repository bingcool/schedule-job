-- P0 Execution Reliability：CAS / Lease / RunOnce Dedup / Timeout / Cancel
-- 在已有库上执行一次。新装请直接使用更新后的 migrations/cron.sql。

ALTER TABLE `cron_task`
    ADD COLUMN `timeout` int NOT NULL DEFAULT 0 COMMENT 'Shell执行超时秒数，0=不限制；HTTP仍用 http_request_time_out' AFTER `retry`;

ALTER TABLE `cron_task_log`
    ADD COLUMN `request_id` bigint unsigned DEFAULT NULL COMMENT '关联 cron_task_run_request.id；调度触发为空，手动执行为请求主键' AFTER `trigger_type`,
    ADD COLUMN `node_id` int unsigned NOT NULL DEFAULT 0 COMMENT '执行节点快照（cron_agent_node.id）；Recovery 按节点收敛，禁止异节点重跑' AFTER `request_id`,
    ADD COLUMN `lease_owner` varchar(128) NOT NULL DEFAULT '' COMMENT 'Execution Lease owner，格式 node_id:worker_pid:boot_id' AFTER `node_id`,
    ADD COLUMN `lease_until` datetime DEFAULT NULL COMMENT 'Lease 过期时间；status=RUNNING 且已过期表示 Worker 可能已崩溃' AFTER `lease_owner`,
    ADD COLUMN `heartbeat_at` datetime DEFAULT NULL COMMENT 'Execution 心跳时间；执行期内由持有 Lease 的 Worker 续租写入' AFTER `lease_until`,
    ADD COLUMN `timeout_at` datetime DEFAULT NULL COMMENT 'Execution 超时截止时间；started_at+timeout，仅 Shell 到期杀进程' AFTER `heartbeat_at`,
    ADD COLUMN `cancelled_at` datetime DEFAULT NULL COMMENT 'Cancel 请求时间；RUNNING→cancel_requested 时写入' AFTER `timeout_at`,
    ADD COLUMN `failure_reason` varchar(64) NOT NULL DEFAULT '' COMMENT '失败原因：WORKER_CRASH/LEASE_EXPIRED/TIMEOUT/CANCELLED/PROCESS_EXIT_ERROR/EXECUTION_ERROR' AFTER `cancelled_at`;

ALTER TABLE `cron_task_log`
    ADD KEY `idx_request_id` (`request_id`) COMMENT '按 RunOnce request_id 查找 Execution，用于 ACK 前去重',
    ADD KEY `idx_lease_running` (`status`, `lease_until`) COMMENT '扫描过期 RUNNING/cancel_requested 做 Crash Recovery',
    ADD KEY `idx_node_status` (`node_id`, `status`) COMMENT '按节点过滤进行中/过期 Execution';

ALTER TABLE `cron_task_log`
    MODIFY COLUMN `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0-register 1-running 2-success 3-failed 4-skipped 5-timeout 6-cancelled 7-unregister 8-cancel_requested';
