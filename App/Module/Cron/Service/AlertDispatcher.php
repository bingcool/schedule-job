<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use Swoolefy\Core\Log\LogManager;
use Swoolefy\Worker\Cron\ExecutionStatus;

/**
 * Execution 终态 CAS 成功后的告警旁路。失败不得影响状态机。
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

        $run = static function () use ($logId): void {
            try {
                (new CronAlertService())->send($logId);
            } catch (\Throwable $e) {
                self::logError($e);
            }
        };

        if (extension_loaded('swoole') && function_exists('goApp') && \Swoole\Coroutine::getCid() >= 0) {
            try {
                goApp($run);

                return;
            } catch (\Throwable $e) {
                self::logError($e);
            }
        }

        $run();
    }

    private static function logError(\Throwable $e): void
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
