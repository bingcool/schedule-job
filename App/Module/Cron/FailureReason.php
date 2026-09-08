<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * Execution 失败/闭合原因。写入 cron_task_log.failure_reason。
 */
final class FailureReason
{
    public const WORKER_CRASH = 'WORKER_CRASH';
    public const LEASE_EXPIRED = 'LEASE_EXPIRED';
    public const TIMEOUT = 'TIMEOUT';
    public const PROCESS_EXIT_ERROR = 'PROCESS_EXIT_ERROR';
    public const SIGNAL_TERMINATED = 'SIGNAL_TERMINATED';
    public const CANCELLED = 'CANCELLED';
    public const EXECUTION_ERROR = 'EXECUTION_ERROR';
}
