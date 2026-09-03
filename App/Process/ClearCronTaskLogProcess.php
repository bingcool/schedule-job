<?php
namespace App\Process;

use Swoolefy\Core\Process\AbstractProcess;

class Tick extends AbstractProcess {

    public function run()
    {
        // todo 每12小时删除cron_task_log表的.env定义的CRON_TASK_LOG_DELETE_DAY的多少天前的日志，避免日志大量增加。默认删除7天前的。
        goTick(12 * 3600 * 1000, function() {

        });
    }
}
