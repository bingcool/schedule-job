<?php
namespace App\Process;

use App\Module\Cron\Service\CronTaskManagerService;
use Swoolefy\Core\Process\AbstractProcess;

/**
 * 创建一个定时器处理进程清理cron_task_log执行日志
 */
class PurgeExpiredTaskLogs extends AbstractProcess {

    public function run()
    {
        $purge = static function (): void {
            (new CronTaskManagerService())->purgeExpiredTaskLogs();
        };

        $purge();
        goTick(12 * 3600 * 1000, $purge, true);
    }
}
