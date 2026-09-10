# schedule-job HTTP Cron 同一 Slot 去重

> 版本：v1.2（时钟偏差用 ±2s 窗口；UNIQUE 仍挡精确重复）  
> 状态：设计方案，尚未落地  
> 目标：同一个任务、同一个调度点，最多一次 HTTP / `proc_open`。  
> 不解决跨 Slot 重叠（那是 `with_block_lapping`），不引入 Leader / Redis Lock。

---

## 1. 现有代码里真正的问题

HTTP 任务（`exec_type = 2`）**不是** Admin（`cli.php` :9502）发的。

现有路径：

```text
Admin 写 cron_task（含 node_id）
        │
        ▼
Agent cron.php
  ScheduleUrlCronProcess  worker_num=1
        │
        ▼
CronTaskService::fetchCronTask(EXEC_URL_TYPE, CRON_NODE_ID)
        │
        ▼
CronManager::onTrigger
        │
        ▼
CronUrlProcess  对本机发起 HTTP
```

任务绑定 `node_id`。一台 Agent 只拉本节点任务。Admin 后面挂多少个负载均衡实例，都不会多打 HTTP。

会打出两次 HTTP 的，是 **同一 `CRON_NODE_ID` 上同时有两套 Scheduler**：

```text
① 两台机器配了同一个 CRON_NODE_ID
② schedule-url-task-cron 的 worker_num > 1
③ life_time / max_handle 重启时新旧进程短暂重叠
```

`with_block_lapping` + `ExecutionGuard` 只在**当前进程内存**里互斥，挡不住上面三种。

P0 Lease / Crash Recovery 管的是 RUNNING 如何闭合，不管「这个 Slot 已经调度过没有」。

---

## 2. 不做的事

```text
Admin 多实例 Leader
Redis Lock / RedLock
把 HTTP 放进 DB 事务
用 cron_task_log 当 Slot 锁
Repository 层（本项目没有）
Scheduler 自己再算一遍 scheduled_at（必须用引擎已有的 plannedAt）
```

手工执行 / RunOnce **不走**本表。它们已有 `request_id`。

跨 Slot 重叠（17:30 还在跑、17:31 又到）继续用 `with_block_lapping`，本表不管。

---

## 3. 为什么独立表

`cron_task_log` 已经有 `scheduled_at`，但不宜直接 UNIQUE `(cron_id, scheduled_at)`：

| 行类型 | 同一秒可能多条 |
|--------|----------------|
| RunOnce | `trigger_type=2`，`scheduled_at` 是「立刻」 |
| SKIPPED | 时间窗 / 本进程重叠也会写一条 |
| 调度 HTTP | `trigger_type=1` |

Slot 占位和 Execution 日志职责不同：

```text
cron_scheduled_task_record  → 这个 Cron Slot 有没有被抢到
cron_task_log               → 这次跑的结果（含 Lease / HTTP status）
```

---

## 4. 表

```sql
CREATE TABLE `cron_scheduled_task_record` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `cron_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT 'cron_task.id',
    `scheduled_at` datetime NOT NULL COMMENT 'Cron Slot,CronManager plannedAt',
    `execution_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '关联cron_task_log.id',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cron_scheduled_at` (`cron_id`, `scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron调度Slot占位';
```

精确同一秒仍靠 UNIQUE，不要先 `exists()` 再 `insert()` 当唯一防线。

不要 `status` 字段。`(cron_id, scheduled_at)` 已是 UNIQUE，范围查询走同一索引，不必再加 `idx_scheduled_at`。

---

## 5. scheduled_at 从哪来

**不要**在 Service 里再 parse 一次 expression。

`CronManager::onTrigger()` 已有：

```text
$planned = $job->nextRunAt
```

写入 Execution 时已是：

```text
scheduled_at = date('Y-m-d H:i:s', $snapshot->plannedAt)
```

Slot 键必须用同一个值。表达式是 Linux Cron 还是秒级 Interval（`>=5`），精度都跟引擎走。不能用 `now()`。

两台机器时钟差 1 秒时，`plannedAt` 可能是 `17:30:00` 和 `17:30:01`，UNIQUE 当成两个 Slot。claim 时再看窗口：

```text
[plannedAt - 2s, plannedAt + 2s]
已有 Record → 视为同一 Slot → skip
```

`2` 写类常量，不要做成 Web 开关。窗口不能再大：秒级 Interval 下限是 5 秒，`2+2=4 < 5`，相邻合法 Slot 不会误伤。改成 ±3s 会和 5 秒任务撞车。

**UNIQUE 管不了范围。** 无锁先查窗口再 INSERT，两边都看到空，仍会插入 `17:30:00` 和 `17:30:01`。必须同一把锁里完成「查窗口 + INSERT」：

```text
BEGIN
  SELECT id FROM cron_task WHERE id = :cronId FOR UPDATE
  SELECT id FROM cron_scheduled_task_record
    WHERE cron_id = :cronId
      AND scheduled_at BETWEEN :planned - 2s AND :planned + 2s
    LIMIT 1
  已有 → ROLLBACK → DUPLICATE
  INSERT cron_scheduled_task_record   -- UNIQUE 挡精确重复
  INSERT cron_task_log（RUNNING）
  UPDATE execution_id
COMMIT
```

锁的是这一条 `cron_task`，只串行化该任务的 claim，时间很短。不要 Redis。`GET_LOCK` 也可以，P0 用行锁更直观。

---

## 6. 挂钩点（对照现有代码）

只拦 **调度触发**（`source=trigger`）。RunOnce 不进本表。

```text
CronManager::runExecutionPipeline
    │
    ├─ 时间窗 skip        → 不 claim（没执行）
    ├─ ExecutionGuard skip → 不 claim（本进程重叠）
    │
    ▼
claim(cron_id, plannedAt)     ← 行锁 + ±2s 窗口 + INSERT，仅 trigger
    │
    ├─ DUPLICATE → return skip，不写 RUNNING，不执行
    ├─ FAILED    → 记错误，不执行
    └─ CREATED   → 事务内已写 Record + RUNNING Execution
    │
    ▼
HTTP / proc_open（现有 Executor，事务已提交）
```

Duplicate（精确冲突或窗口内已有）是正常竞争，INFO/DEBUG，不要当 ERROR。

HTTP / `proc_open` 在 COMMIT 之后。失败 / Timeout 只更新 `cron_task_log`，**不删** Scheduled Record。

实现落在 App 侧：

```text
CronScheduledTaskRecordEntity
CronScheduledTaskRecordService::claim(cronId, scheduledAt): CREATED|DUPLICATE|FAILED
```

`ExecutionService` 继续管 CAS / Lease。不要把 Slot 唯一性塞进状态机。

Vendor `CronManager` 只需在 `writeLog(RUNNING)` 之前加一个可空回调（类似已有 `run_once_precheck`），Duplicate 时按 skip 返回。不要写 Controller。

---

## 7. Shell 是否一起做

Shell 和 HTTP 共用 `CronManager::onTrigger`。同一 `CRON_NODE_ID` 双 Worker 时，Shell 也会跑两遍。

**P0 挂钩一次，调度触发都 claim**（不按 exec_type 分支）。文档以 HTTP 为例，只因为远程接口副作用更显眼。

---

## 8. 删除 / 禁用任务

Scheduler 不再给停用任务 arm Timer（现有 Runtime Diff）。历史 Record 保留，不当作级联删除。

---

## 9. P0 范围

```text
① 表 + UNIQUE(cron_id, scheduled_at)
② trigger 用 plannedAt 做 Slot
③ claim：行锁 + ±2s 窗口 + INSERT；CREATED 才执行
④ DUPLICATE（精确或窗口内）→ skip，不当异常
⑤ 失败不删 Record
⑥ RunOnce / 手工不走本表
```

运维上 `schedule-url-task-cron` / `schedule-fork-task-cron` 保持 `worker_num=1`，每台机器 `CRON_NODE_ID` 唯一。这是配置约束，**不能代替** UNIQUE。

---

## 10. 明确不做

```text
Scheduler Leader
Redis Lock
MQ
在 Scheduled Record 里存 HTTP 响应
Metrics（可后补）
```

---

## 11. 测试（对照现有路径）

同一 Agent 节点、同一 `cron_id`、同一 `plannedAt`：

| 场景 | 预期 |
|------|------|
| 单 Worker 触发一次 | Record=1 Execution=1 HTTP=1 |
| 两个 Worker / 两个同 node_id 进程 | Record=1 Execution=1 执行=1，另一个 DUPLICATE skip |
| plannedAt 相差 1s（时钟偏差） | 后到的窗口命中，skip，不执行 |
| plannedAt 相差 5s（合法下一格） | 两条 Record，两次执行 |
| 同一进程重复扫描同一 Slot | 第二次 DUPLICATE |
| HTTP 失败后再扫同一 Slot | 不重发 |
| RunOnce | 不写本表，现有 request_id 去重不变 |
| 不同 cron_id 同一时间 | 两条 Record，两次执行 |
| 时间窗 skip | 不写 Record |

---

## 12. 结论

> **Scheduled Record 占住 Cron Slot；只有 claim 成功的那次 trigger 才能创建 Execution 并执行。**

精确重复靠 UNIQUE；差 1～2 秒靠行锁里的窗口查询。两者都要，缺一不可。
