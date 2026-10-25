<?php
declare(strict_types=1);

namespace App\Process;

use App\Module\Cron\Service\AlertDispatcher;
use App\Module\Cron\Service\CronAlertService;
use Swoolefy\Core\Process\AbstractProcess;

/**
 * Admin 定时扫描 Agent 心跳，节点失联时向所属节点组机器人推告警；并清理过期警告记录。
 */
class ScanNodeLivenessAlerts extends AbstractProcess
{
    public function run()
    {
        $scan = static function (): void {
            try {
                (new CronAlertService())->scanOfflineNodes();
            } catch (\Throwable $e) {
                AlertDispatcher::logError($e);
            }
        };

        $purge = static function (): void {
            try {
                (new CronAlertService())->purgeExpiredAlertLogs(7);
            } catch (\Throwable $e) {
                AlertDispatcher::logError($e);
            }
        };

        $scan();
        goTick(15 * 1000, $scan, true);

        $purge();
        goTick(12 * 3600 * 1000, $purge, true);
    }
}
