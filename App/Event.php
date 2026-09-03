<?php
/**
 * +----------------------------------------------------------------------
 * | swoolefy framework bases on swoole extension development, we can use it easily!
 * +----------------------------------------------------------------------
 * | Licensed ( https://opensource.org/licenses/MIT )
 * +----------------------------------------------------------------------
 * | @see https://github.com/bingcool/swoolefy
 * +----------------------------------------------------------------------
 */

namespace App;

use Swoolefy\Core\EventHandler;
use Swoolefy\Core\Process\ProcessManager;
use Swoolefy\Core\SystemEnv;

class Event extends EventHandler
{
    /**
     * onInit
     */
    public function onInit() {

        if (!SystemEnv::isWorkerService()) {
            // 创建一个定时器处理进程清理执行日志
            ProcessManager::getInstance()->addProcess('tick', \App\Process\Tick::class);
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