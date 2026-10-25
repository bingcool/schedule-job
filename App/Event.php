<?php
declare(strict_types=1);

namespace App;

use App\Module\Cron\CronTaskLogMessageConfig;
use Swoolefy\Core\EventHandler;
use Swoolefy\Core\Process\ProcessManager;
use Swoolefy\Core\SystemEnv;

class Event extends EventHandler
{
    /**
     * onInit
     */
    public function onInit() {
        CronTaskLogMessageConfig::maxBytes();

        if (!SystemEnv::isWorkerService()) {
            // 创建一个定时器处理进程清理执行日志
            ProcessManager::getInstance()->addProcess('tick-purge-task-log', \App\Process\PurgeExpiredTaskLogs::class);
            ProcessManager::getInstance()->addProcess('tick-node-liveness-alert', \App\Process\ScanNodeLivenessAlerts::class);
        }
    }

    /**
     * onWorkerServiceInit
     */
    public function onWorkerServiceInit()
    {
        // todo refer to Test Demo
    }

    /**
     * onWorkerStart
     * @param $server
     * @param $worker_id
     * @return void
     */
    public function onWorkerStart($server, $worker_id)
    {
        Autoloader::preloadAppClasses();

        if (!SystemEnv::isWorkerService()) {
            // todo
        }
    }

    /**
     * @param $server
     * @param $worker_id
     * @return void
     */
    public function onWorkerStop($server, $worker_id)
    {
        // todo
    }
}