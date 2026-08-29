<?php

use Swoolefy\Worker\Cron\CronProcess;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Test\Scripts\Kernel;

// 定时fork进程处理任务
return [
        [
        'process_name' => 'schedule-fork-task-cron', // 进程名称
        'handler' => \Swoolefy\Worker\Cron\CronForkProcess::class,
        'description' => '系统fork模式任务调度',
        'worker_num' => 1, // 默认动态进程数量
        'max_handle' => 100, //消费达到10000后reboot进程
        'life_time'  => 3600, // 每隔3600s重启进程
        'limit_run_coroutine_num' => 10, // 当前进程的实时协程数量，如果协程数量超过此设置的数量，则禁止继续消费队列处理业务，而是在等待
        'extend_data' => [],
        'args' => [
            // CronManager 唯一调度：配置轮询间隔（秒）。fetcher 抛异常时保留 Last Known Good Runtime。
            'cron_poll_interval' => env('CRON_POLL_INTERVAL', 20),
            'node_id' => env('CRON_NODE_ID'),
            // 节点心跳间隔（秒, start 立刻 ack 一次，再按此间隔 tick。
            'heartbeat_interval' => env('CRON_HEARTBEAT_INTERVAL', 15),
            // 跨进程 Manual Run：Admin 入队后由本 Polling 执行 runOnceNow，再 ack 清队列
            'run_once_ack' => static function (string $jobId, int $cronTaskId, $result = null, int $requestId = 0): void {
                unset($jobId, $cronTaskId, $result);
                (new \App\Module\Cron\Service\CronTaskService())->ackRunOnce($requestId);
            },
            // 节点心跳落库：upsert cron_agent_node.last_heartbeat_at / heartbeat_interval
            'node_heartbeat_ack' => static function (string $nodeId, int $heartbeatInterval = 15): void {
                (new \App\Module\Cron\Service\CronTaskService())->ackNodeHeartbeat($nodeId, $heartbeatInterval);
            },
            // 动态定时任务列表
            'task_list' => function () {
                // 读取数据库cronTask配置模式
                $taskList = (new \App\Module\Cron\Service\CronTaskService())
                    ->fetchCronTask(CronProcess::EXEC_FORK_TYPE, env('CRON_NODE_ID'));
                // 返回taskList
                if (!empty($taskList)) {
                    return $taskList;
                } else {
                    return [];
                }
            }
        ],
    ],
];