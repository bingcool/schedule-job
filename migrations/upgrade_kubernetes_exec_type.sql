-- ---------------------------------------------------------------------------
-- exec_type=3（Kubernetes 一次性 Job）增量迁移
--
-- 对应方案：docs/schedule-job-k8s-deployment.md §5.1
--
-- 为什么不复用 cron_task.command：
--   command 是 varchar(256)，且 exec_type=1 存 shell 命令、=2 存 URL，语义已占满。
--   Kubernetes 需要 namespace/deployment/container/command[]/args[] 这样的结构化配置，
--   塞进 command 既会超长，也无法校验。因此单开一个 JSON 列。
--
--   对 exec_type=3 而言 command 只是给 UI 看的一行摘要（如 "order-service /app/bin/task"），
--   **不是**执行权威来源；真正的执行参数只看 k8s_spec，镜像只看执行当下的 Deployment 模板。
--
-- 兼容性：新增可空列 + 改注释，对存量 exec_type=1/2 的任务无影响，可在线执行。
-- ---------------------------------------------------------------------------

ALTER TABLE `cron_task`
    MODIFY COLUMN `exec_type` tinyint(2) NOT NULL DEFAULT '1'
        COMMENT '执行类型 1-GLUE(shell)，2-http，3-kubernetes';

ALTER TABLE `cron_task`
    ADD COLUMN `k8s_spec` json DEFAULT NULL
        COMMENT 'exec_type=3：{namespace,deployment,container,command[],args[]}；不存image，执行时现取Deployment模板'
        AFTER `http_request_time_out`;
