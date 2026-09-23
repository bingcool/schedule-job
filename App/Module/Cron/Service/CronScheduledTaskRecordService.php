<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\Repository\CronScheduledTaskRecordRepository;
use App\Module\Cron\Repository\CronTaskRepository;
use Swoolefy\Worker\Cron\CronScheduleSlotClaimConst;

/**
 * 调度触发抢占 Cron Slot（写入 cron_scheduled_task_record）。
 *
 * 保证：同一 cron_id + 同一调度点（含 ±{@see CronScheduleSlotClaimConst::SKEW_SECONDS} 窗口）
 * 全局最多一条 Record，从而最多一次 HTTP / proc_open。
 *
 * 仅 Agent 的 `schedule_slot_claim` 回调使用。RunOnce / 手工执行不走本表。
 * 返回值必须是 {@see CronScheduleSlotClaimConst} 三态，供 CronManager 门闩使用。
 */
class CronScheduledTaskRecordService
{
    private CronTaskRepository $taskRepository {
        get => $this->taskRepository ??= new CronTaskRepository();
    }

    private CronScheduledTaskRecordRepository $scheduleRecordRepository {
        get => $this->scheduleRecordRepository ??= new CronScheduledTaskRecordRepository();
    }

    /**
     * 抢占一个调度点。非法参数直接 FAILED，不打库。
     *
     * @return CronScheduleSlotClaimConst::CREATED|CronScheduleSlotClaimConst::DUPLICATE|CronScheduleSlotClaimConst::FAILED
     */
    public function claim(int $cronId, int $plannedAt): string
    {
        if ($cronId <= 0 || $plannedAt <= 0) {
            return CronScheduleSlotClaimConst::FAILED;
        }

        return $this->claimOnce($cronId, $plannedAt);
    }

    /**
     * 赢家首条 RUNNING Execution 落库后回写 execution_id。
     *
     * Record 在 claim 时 execution_id=0；执行开始后才绑定，便于对账。
     * 找不到对应 Record（已被清、或根本没 claim）则忽略，不抛。
     */
    public function bindExecution(int $cronId, string $scheduledAt, int $executionId): void
    {
        $this->scheduleRecordRepository->bindExecution($cronId, $scheduledAt, $executionId);
    }

    /**
     * 事务内：锁 cron_task → 窗口查重 → INSERT。
     *
     * 1. SELECT cron_task FOR UPDATE：任务已删则 FAILED
     * 2. 窗口 [plannedAt ± SKEW] 已有 Record → DUPLICATE（吸收时钟偏差）
     * 3. INSERT；UNIQUE(cron_id, scheduled_at) 撞车（1062）→ DUPLICATE
     * 4. 其余异常 → FAILED；失败不删已有 Record
     *
     * @return CronScheduleSlotClaimConst::CREATED|CronScheduleSlotClaimConst::DUPLICATE|CronScheduleSlotClaimConst::FAILED
     */
    private function claimOnce(int $cronId, int $plannedAt): string
    {
        $scheduledAt = date('Y-m-d H:i:s', $plannedAt);
        $from = date('Y-m-d H:i:s', $plannedAt - CronScheduleSlotClaimConst::SKEW_SECONDS);
        $to = date('Y-m-d H:i:s', $plannedAt + CronScheduleSlotClaimConst::SKEW_SECONDS);

        $conn = $this->taskRepository->getConnection();
        $conn->beginTransaction();
        try {
            $task = $this->taskRepository->findByIdForUpdate($cronId);
            if (!$task) {
                $conn->rollback();

                return CronScheduleSlotClaimConst::FAILED;
            }

            if ($this->scheduleRecordRepository->existsInSkewWindow($cronId, $from, $to)) {
                $conn->rollback();

                return CronScheduleSlotClaimConst::DUPLICATE;
            }

            $this->scheduleRecordRepository->insert([
                'cron_id' => $cronId,
                'scheduled_at' => $scheduledAt,
                'execution_id' => 0,
            ]);
            $conn->commit();

            return CronScheduleSlotClaimConst::CREATED;
        } catch (\Throwable $e) {
            try {
                $conn->rollback();
            } catch (\Throwable) {
            }
            if ($this->scheduleRecordRepository->isDuplicateKey($e)) {
                return CronScheduleSlotClaimConst::DUPLICATE;
            }

            return CronScheduleSlotClaimConst::FAILED;
        }
    }
}
