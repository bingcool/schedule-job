<?php

declare(strict_types=1);

namespace App\WorkerCron;

use App\Module\Cron\Kubernetes\KubernetesExecutionHook;
use App\Module\Cron\Service\ExecutionService;
use Swoolefy\Worker\Cron\CronK8sProcess;
use Swoolefy\Worker\Cron\KubernetesExecutionHookInterface;

/**
 * Kubernetes Cron Worker（exec_type=3）。
 *
 * 只做两件事：启动时初始化 Agent 运行时（Lease / Guard Timer），
 * 以及给框架的 {@see CronK8sProcess} 注入 schedule-job 自己的落库与取消钩子。
 *
 * 没有这个钩子，Admin 的取消请求只会把 Execution 标成 CANCELLED，集群里的 Job
 * 仍在跑（方案 §11.1 的孤儿 Job）。
 */
class ScheduleK8sCronProcess extends CronK8sProcess
{
    /**
     * 用 `cron_task_log` 支撑 Job 名落库、取消删 Job、元数据回写。
     */
    protected function createKubernetesHook(): KubernetesExecutionHookInterface
    {
        return new KubernetesExecutionHook();
    }

    public function run()
    {
        (new ExecutionService())->bootAgent();
        parent::run();
    }
}
