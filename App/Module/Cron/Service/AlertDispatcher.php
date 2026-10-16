<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\RobotAlertType;
use Swoolefy\Core\Log\LogManager;
use Swoolefy\Worker\Cron\ExecutionStatus;

/**
 * Execution 终态 CAS / 节点存活边沿 CAS 成功后的告警旁路。失败不得影响状态机。
 */
final class AlertDispatcher
{
    public static function dispatchIfNeeded(int $logId, int $toStatus): void
    {
        if ($logId <= 0) {
            return;
        }
        if (!in_array($toStatus, [ExecutionStatus::FAILED, ExecutionStatus::TIMEOUT], true)) {
            return;
        }

        self::runSafely(static function () use ($logId): void {
            (new CronAlertService())->send($logId);
        });
    }

    public static function dispatchNodeLiveness(int $nodeId, int $alertType, string $lastHeartbeatAt): void
    {
        if ($nodeId <= 0 || !RobotAlertType::isNodeLiveness($alertType)) {
            return;
        }

        self::runSafely(static function () use ($nodeId, $alertType, $lastHeartbeatAt): void {
            $svc = new CronAlertService();
            if ($alertType === RobotAlertType::NODE_ONLINE) {
                $svc->sendNodeOnline($nodeId, $lastHeartbeatAt);
            } else {
                $svc->sendNodeOffline($nodeId, $lastHeartbeatAt);
            }
        });
    }

    /**
     * @param callable():void $run
     */
    private static function runSafely(callable $run): void
    {
        $wrapped = static function () use ($run): void {
            try {
                $run();
            } catch (\Throwable $e) {
                self::logError($e);
            }
        };

        if (\Swoole\Coroutine::getCid() >= 0) {
            try {
                goApp($wrapped);
                return;
            } catch (\Throwable $e) {
                self::logError($e);
            }
        }

        $wrapped();
    }

    public static function logError(\Throwable $e): void
    {
        $msg = '[cron-alert] ' . $e->getMessage();
        try {
            $logger = LogManager::getInstance()->getLogger('error_log');
            if (is_object($logger) && method_exists($logger, 'addError')) {
                $logger->addError($msg);

                return;
            }
        } catch (\Throwable) {
        }
        error_log($msg);
    }
}
