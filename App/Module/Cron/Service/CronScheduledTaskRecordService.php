<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\Entity\CronScheduledTaskRecordEntity;
use App\Module\Cron\Entity\CronTaskEntity;
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
        if ($cronId <= 0 || $scheduledAt === '' || $executionId <= 0) {
            return;
        }
        CronScheduledTaskRecordEntity::query()
            ->where('cron_id', $cronId)
            ->where('scheduled_at', $scheduledAt)
            ->where('execution_id', 0)
            ->update(['execution_id' => $executionId]);
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

        $conn = (new CronTaskEntity())->getConnection();
        $conn->beginTransaction();
        try {
            $task = CronTaskEntity::queryNotDeleted()
                ->where('id', $cronId)
                ->setOption('lock', true)
                ->find();
            if (!$task) {
                $conn->rollback();

                return CronScheduleSlotClaimConst::FAILED;
            }

            $exists = CronScheduledTaskRecordEntity::query()
                ->where('cron_id', $cronId)
                ->where('scheduled_at', '>=', $from)
                ->where('scheduled_at', '<=', $to)
                ->field('id')
                ->find();
            if ($exists) {
                $conn->rollback();

                return CronScheduleSlotClaimConst::DUPLICATE;
            }

            CronScheduledTaskRecordEntity::query()->insert([
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
            if ($this->isDuplicateKey($e)) {
                return CronScheduleSlotClaimConst::DUPLICATE;
            }

            return CronScheduleSlotClaimConst::FAILED;
        }
    }

    /**
     * MySQL UNIQUE 冲突：errno 1062 或文案 Duplicate entry。
     */
    private function isDuplicateKey(\Throwable $e): bool
    {
        if ($e instanceof \PDOException) {
            $driver = (int) ($e->errorInfo[1] ?? 0);
            if ($driver === 1062) {
                return true;
            }
        }
        $msg = $e->getMessage();

        return str_contains($msg, '1062') || str_contains($msg, 'Duplicate entry');
    }
}
