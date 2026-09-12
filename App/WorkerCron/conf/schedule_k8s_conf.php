<?php

use Swoolefy\Worker\Cron\CronProcess;

/**
 * Kubernetes 一次性 Job 任务 Worker（exec_type=3）。
 *
 * 该进程必须跑在**具备集群凭证**的节点上：集群内用 ServiceAccount 自动发现，
 * 集群外配 K8S_API_SERVER + K8S_TOKEN。所有 exec_type=3 的任务都应绑定到这个
 * 节点的 CRON_NODE_ID —— Agent 是按 node_id + exec_type 拉任务的，绑错节点就永远不会执行。
 *
 * 参数取值与 fork/url Worker 的差异（方案 §11.2）：
 *
 * - worker_num=1：多进程会在同一 node_id 上跑出两个 Scheduler，Slot 唯一键虽能兜住，
 *   但只会白白产生 DUPLICATE 噪音。
 * - life_time 更短（1h）：Job 期间协程一直被占用，而 life_time 到点 reboot 会等所有
 *   在跑的协程结束。配合「K8s 任务强制 timeout>0」，reboot 最多被拖一个 timeout。
 * - limit_run_coroutine_num=50：K8s 任务是分钟级的长占用，不能照抄 fork 的 200。
 *   这个值 ≈ 本节点允许同时在跑的 Job 数。
 * - max_handle 不设小值：一次执行就消耗一个 handle，长任务本来就跑不了几次。
 */
return [
    [
        'process_name' => 'schedule-k8s-task-cron', // 进程名称
        'handler' => \App\WorkerCron\ScheduleK8sCronProcess::class,
        'worker_num' => 1, // 必须为 1：同一节点只允许一个 Scheduler
        'max_handle' => 500,
        'life_time'  => (int) env('CRON_K8S_WORKER_LIFE_TIME', 86400),
        'limit_run_coroutine_num' => (int) env('CRON_K8S_MAX_CONCURRENCY', 1000),
        'extend_data' => [],
        'args' => [
            // CronManager 唯一调度：配置轮询间隔（秒）。
            'cron_poll_interval' => env('CRON_POLL_INTERVAL', 20),
            'node_id' => env('CRON_NODE_ID'),
            // 跨进程 Manual Run：Admin 入队后由本 Polling 执行 runOnceNow，再 ack 清队列
            'run_once_ack' => static function (string $jobId, int $cronTaskId, $result = null, int $requestId = 0): void {
                unset($jobId, $cronTaskId, $result);
                (new \App\Module\Cron\Service\CronTaskService())->ackRunOnce($requestId);
            },
            'run_once_precheck' => static function (int $requestId): string {
                return (new \App\Module\Cron\Service\ExecutionService())->precheckRunOnce($requestId);
            },
            // 同一 cron_id + 调度点只允许一个赢家；返回 CronScheduleSlotClaimConst::CREATED|DUPLICATE|FAILED。RunOnce 不走。
            'schedule_slot_claim' => static function (int $cronTaskId, int $plannedAt): string {
                return (new \App\Module\Cron\Service\CronScheduledTaskRecordService())->claim($cronTaskId, $plannedAt);
            },
            'heartbeat_interval' => env('CRON_HEARTBEAT_INTERVAL', 15),
            'node_heartbeat_ack' => static function (string $nodeId, int $heartbeatInterval = 15): void {
                (new \App\Module\Cron\Service\CronTaskService())->ackNodeHeartbeat($nodeId, $heartbeatInterval);
            },

            // 动态定时任务列表
            'task_list' => function () {
                $taskList = (new \App\Module\Cron\Service\CronTaskService())
                    ->fetchCronTask(
                        CronProcess::EXEC_K8S_TYPE,
                        env('CRON_NODE_ID'),
                        (string) env('CRON_NODE_API_KEY', ''),
                    );

                return !empty($taskList) ? $taskList : [];
            }
        ],
    ]
];
