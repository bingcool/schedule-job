# schedule-job P0 严重 Bug 修复技术方案

> 修复范围：仅针对当前代码审计确认的两个 P0 正确性问题：Lease Recovery CAS 竞争、RunOnce Dedup 非原子重复执行。本方案不新增业务功能。
>
> 已按仓库现状校正：`cron_task_log` 字段类型、`transition()` / `writeLog()` / `precheckRunOnce()` / K8s Crash Recovery 的真实路径。原方案里与代码冲突的假设已改掉。

## 1. 修复目标

### P0-1 Lease Recovery CAS

当前 Recovery 只按 `id + status` 更新，存在 Worker Heartbeat 与 Recovery 并发时，Recovery 使用旧快照把仍在运行的任务错误标记终态，并可能进一步 SIGKILL 活跃进程的问题。

证据（现状）：

- `ExecutionService::transition()` 的 WHERE 只有 `id + status`
- `recoverRow()` / `recoverExpiredLeases()` / `precheckRunOnce()` 都走这条 CAS
- `recoverExpiredLeases()` 在 `recoverRow()` 成功后，用 SELECT 快照里的 `pid` 对本节点进程 `SIGKILL`

旧：

```sql
WHERE id = ?
AND status = ?
```

改为：

```sql
WHERE id = ?
AND status = ?
AND lease_owner = old_owner
AND lease_until = old_lease_until
AND lease_until < recovery_now
```

核心原则：**Recovery 只能恢复它读取到的那一版 Lease。CAS 失败后不得 terminate。**

### P0-2 RunOnce Dedup

当前 RunOnce 依赖 `SELECT pending → precheckRunOnce() → 执行 → ack`，不是原子 Claim。两个 Agent 可以同时通过检查并执行同一个请求。

改为：

```text
RunOnce Request
      ↓
INSERT cron_task_log（RUNNING，request_id 有值）
      ↓
UNIQUE(request_id)
 ┌────┴────┐
成功       冲突
 │          │
 ▼          ▼
允许执行    不执行（按已有 Execution 决定 ack / defer）
```

核心原则：**带 `request_id` 的 RUNNING INSERT 本身就是 RunOnce 的执行 Claim。**

这和调度 Slot 已落地的模式同构：`cron_scheduled_task_record` 用 `UNIQUE(cron_id, scheduled_at)` 做 Claim，1062 视为 DUPLICATE，输家不进执行器。RunOnce 不走 Slot 表，Claim 落在 `cron_task_log.request_id`。

---

# 2. P0-1 Lease Recovery CAS

## 2.1 当前竞态

假设 Execution：

```text
id = 100
status = RUNNING
lease_owner = worker-A
lease_until = 10:00:30
```

`recoverExpiredLeases()` 在 10:00:31 读到旧快照；随后 Worker A 在 10:00:31.1 Heartbeat，将 `lease_until` 延长到 10:01:01。如果 Recovery 仍执行：

```sql
UPDATE cron_task_log
SET status = FAILED
WHERE id = 100
AND status = RUNNING;
```

则 UPDATE 仍可能成功，造成误恢复；成功后还会用快照 `pid` 对本节点发 SIGKILL。

`precheckRunOnce()` 对过期 RUNNING 同样调用 `recoverRow()`，竞态相同。

## 2.2 正确 CAS

Recovery 必须保存读取时的：

- `old_owner = lease_owner`（列是 `varchar NOT NULL DEFAULT ''`，比较空串，不要当 NULL）
- `old_lease_until = lease_until`
- `recovery_now = 当前时间`（与候选 SELECT 使用同一时刻，避免 SELECT 与 UPDATE 各取一次 `now`）

然后执行：

```sql
UPDATE cron_task_log
SET
    status = ?,              -- 由 Recovery 语义决定，不是固定 FAILED
    finished_at = ?,
    failure_reason = ?,
    message = ?
WHERE id = ?
AND status = ?
AND lease_owner = ?
AND lease_until = ?
AND lease_until < ?;
```

`lease_until = old_lease_until` 是关键：Heartbeat 只要已经续租，旧 Recovery CAS 就必须失败。

同时比较 `lease_owner`，形成完整的 Lease Snapshot 校验。

不要把 `lease_owner` 写成 NULL。表定义是：

```sql
`lease_owner` varchar(128) NOT NULL DEFAULT ''
`lease_until` datetime DEFAULT NULL
```

终态后 `lease_until` 可以置 NULL（避免再被过期扫描扫到），`lease_owner` 建议保留审计，或写成 `''`。不是正确性必要条件。

`duration_ms` 现网 Recovery 不写，本次不必为了 CAS 补上。

## 2.3 CAS 结果

### affected_rows = 1

表示 Recovery 成功取得恢复权，可以继续后续 terminate / K8s DELETE。

### affected_rows = 0

表示并发状态已经变化，例如：

- Heartbeat 已成功续租
- 其他 Worker 已更新 Lease
- 状态已经变化
- 当前 Lease 已不满足 Recovery 条件

这不是系统异常，而是正常并发竞争。**CAS 失败后不得 SIGKILL，也不得删除 Kubernetes Job。**

## 2.4 Recovery 与 Kill 的严格边界

必须保证：

```text
Recovery CAS
   │
   ├── 成功 → 才允许后续 terminate
   │
   └── 失败 → STOP，不得 SIGKILL / 不得删 K8s Job
```

否则即使状态 CAS 正确，仍可能使用旧 PID 误杀其他进程。

现网 `recoverExpiredLeases()` 已限制：仅 `node_id` 等于本节点且 `pid > 0` 才 `signalPid(9)`。这个约束保留。PID 复用风险不在本次范围。

### 2.4.1 Shell 与 K8s 顺序不同

现网 `recoverRow()` 会先调 `KubernetesCrashRecovery::resolve()`，再 `transition()`。`resolve()` 在 `CANCEL_REQUESTED` 且 Job 仍 Active 时会先 DELETE Job。若此时 Heartbeat 已续租，就会在 CAS 失败前误删仍属于活跃 Worker 的 Job。

按执行类型拆开：

```text
Shell / HTTP
  候选 SELECT
      ↓
  Lease Snapshot CAS（通常落到 FAILED / CANCELLED）
      ↓
  成功才 SIGKILL（仅本节点 + pid>0）

Kubernetes
  候选 SELECT
      ↓
  只读观察 Job（Get，不 Delete）
      ↓
  DEFER（Job 仍 Active / API 不可达）→ 本轮不 CAS、不删 Job
      ↓
  已有终态或确认可收尾 → Lease Snapshot CAS（status 用观察结果：SUCCESS / TIMEOUT / FAILED / CANCELLED）
      ↓
  CAS 成功后才允许 DELETE Job
```

不能先 CAS 成 FAILED 再去看 K8s：那会把仍在跑的 Job 错标失败，也破坏现网「Active / 集群不可达则 defer」的语义。

## 2.5 RuntimeGuard 顺序

当前 `ExecutionRuntimeGuard::onTick()` 为 Recovery 后 Heartbeat，建议调整为：

```text
Heartbeat
   ↓
Lease Recovery
   ↓
Timeout / Cancel
```

这可以降低同一 Worker 因事件循环短暂延迟而先把自己的任务判定为过期的概率。

但必须明确：**Heartbeat 顺序调整不是 P0 修复本身，真正的正确性边界仍然是 Lease Snapshot CAS。**

## 2.6 precheckRunOnce 必须统一 Recovery CAS

`precheckRunOnce()` 对过期 RUNNING 不能继续 `transition(id, RUNNING, FAILED)`。必须复用同一套：

```text
old lease_owner
old lease_until
status
recovery_now
```

建议抽象统一方法，例如：

```php
recoverExpiredExecution(
    array $execution,
    DateTimeInterface $recoveryNow
): RecoveryResult
```

至少区分：

```text
RECOVERED
CAS_CONFLICT
NOT_EXPIRED
DEFERRED          -- K8s Job 仍在跑 / API 不可达
NOT_RECOVERABLE
```

`precheckRunOnce()` 的返回值语义保持现网三态：`execute` / `ack` / `defer`。CAS 失败或 DEFERRED 只能 `defer`，不能当成「已恢复、可以再跑一次」。

## 2.7 查询只是候选，不是 Recovery 权限

候选查询可以保持：

```sql
SELECT id, status, pid, lease_owner, lease_until, started_at, node_id
FROM cron_task_log
WHERE status IN (?, ?)
AND lease_until IS NOT NULL
AND lease_until < ?;
```

但 SELECT 只是找到候选任务。真正的 Recovery 权限必须由带 Lease 快照条件的 UPDATE CAS 决定。

---

# 3. P0-2 RunOnce Dedup

## 3.1 当前竞态

现网路径（`CronManager::consumeRunOnceRequests()` + `ExecutionService::precheckRunOnce()`）：

```text
fetcher 列出 pending request
      ↓
precheckRunOnce：SELECT cron_task_log WHERE request_id = ?
      ↓
无行 / FAILED → execute
终态 SUCCESS/TIMEOUT/CANCELLED → ack
RUNNING 且租约有效 / CANCEL_REQUESTED → defer
RUNNING 且租约过期 → recoverRow 后 execute
      ↓
runOnceNow()
      ↓
ackRunOnce()
```

`idx_request_id` 只是普通索引，注释写的是「ACK 前去重」。并发时：

```text
Agent A                  Agent B
读取 request 1001        读取 request 1001
precheck 无 Execution    precheck 无 Execution
writeLog(RUNNING)        writeLog(RUNNING)
Shell / HTTP / K8s       Shell / HTTP / K8s
ack                       ack
```

导致同一 RunOnce 实际执行两次。

补充：`runExecutionPipeline()` **已经**在 `runWithRetry()` 之前 `writeLog(RUNNING)`。问题不是「先副作用再落库」，而是 **INSERT 没有唯一约束，且 `invokeLogWriter()` 把写库异常全部吞掉**。只加 UNIQUE、不改这条链路，输家 INSERT 失败会被隔离，随后仍然进入执行器。

## 3.2 数据库唯一约束

将：

```text
cron_task_log.request_id
```

作为数据库唯一 Claim 边界：

```sql
UNIQUE KEY uk_request_id (request_id)
```

实施前必须检查历史重复：

```sql
SELECT request_id, COUNT(*) AS cnt
FROM cron_task_log
WHERE request_id IS NOT NULL
  AND request_id > 0
GROUP BY request_id
HAVING COUNT(*) > 1;
```

存在重复时先处理历史脏数据，再增加 UNIQUE。

## 3.3 request_id 现状（不要改类型）

`migrations/cron.sql` 已是：

```sql
`request_id` bigint unsigned DEFAULT NULL
KEY `idx_request_id` (`request_id`)
```

`writeRuntime()` 在 `request_id <= 0` 时会 `unset`，普通 Cron INSERT 不带该列，落库为 NULL。

因此：

- **不要** `MODIFY COLUMN` 改类型
- **不要**按 `request_id = ''` 清洗（BIGINT 不会存空串；`''` 会被当成 `0`）
- 需要检查的是 `0` 和重复值

```sql
SELECT COUNT(*) FROM cron_task_log WHERE request_id = 0;

UPDATE cron_task_log
SET request_id = NULL
WHERE request_id = 0;
```

统一语义：

```text
RunOnce Claim / 终态保留 → request_id = 实际 request id
SKIPPED / 配置变更 / 普通 Cron → request_id = NULL
```

MySQL UNIQUE 允许多条 NULL，因此普通 Cron 不受影响。

推荐 Migration：

```sql
-- 先清洗 0 和重复，再：
ALTER TABLE cron_task_log
DROP INDEX idx_request_id,
ADD UNIQUE KEY uk_request_id (request_id);
```

字段类型以当前 Schema 为准，不得为此改成 `varchar`。

## 3.4 正确 Claim 流程

必须做成和 `schedule_slot_claim` 一样的门闩，不能指望「INSERT 失败被 logWriter 吞掉」：

```text
RunOnce Request
      ↓
时间窗 / 重叠守卫（现网：SKIPPED 不 ack，下一轮再试）
      ↓
INSERT Execution(status=RUNNING, request_id=?)
      ↓
UNIQUE(request_id)
      │
 ┌────┴────┐
成功       Duplicate 1062
 │          │
 ▼          ▼
获得执行权  查已有 Execution
 │          │
 ▼          ├─ RUNNING 且租约有效 → defer，不 ack
 │          ├─ 终态 → ack，不执行
 │          └─ 过期 RUNNING → 走统一 Recovery CAS，再按结果 ack / defer
 │
 ▼
进入执行器（Shell / HTTP / K8s）
```

禁止继续把下面这段当正确性边界：

```php
if (!$executionExists($requestId)) {
    createExecution();
}
```

`SELECT + INSERT` 以及现网 `precheckRunOnce()` 都只是快路径，可以保留以减少无效管线，但不再是幂等边界。

落地时二选一（推荐 A，与 Slot Claim 对称）：

**A. 在 CronManager 增加 RunOnce Claim 门闩**（类似 `schedule_slot_claim`）

- WorkerCron 三个 conf 增加 `run_once_claim`
- `runExecutionPipeline()` 在 `writeLog(RUNNING)` 之前调用
- CREATED 才写 RUNNING 并执行；DUPLICATE 立即停止
- `insertExecution()` 识别 MySQL 1062（复用 `CronScheduledTaskRecordService::isDuplicateKey()` 的判定）

**B. 让 RUNNING INSERT 本身当 Claim**

- `insertExecution()` 遇 1062 抛出可识别的 Claim 冲突
- **必须改** swoolefy `CronManager::invokeLogWriter()`：RunOnce 的 RUNNING 写库失败不能再隔离后继续 `runWithRetry()`

只改 schedule-job、不改 CronManager，P0-2 修不掉。

## 3.5 Execution 必须先于真实副作用

现网顺序已经是：

```text
writeLog(RUNNING) → runWithRetry() → Shell / HTTP / K8s
```

本次保持这个顺序，补上 Claim 失败短路。禁止退回：

```text
Shell → 再创建 Execution
HTTP → 再创建 Execution
K8s Job → 再创建 Execution
```

因此：

- Shell：只有 Claim 成功后才允许 `proc_open`
- HTTP：只有 Claim 成功后才允许发送请求
- K8s：只有 Claim 成功后才允许创建 Job

## 3.6 SKIPPED 不得占用 UNIQUE

`CronManager::recordSkip()` 目前会把 `request_id` 写进 SKIPPED 行。时间窗 / `with_block_lapping` 跳过时：

- `ExecutionResult::isCompleted()` 为 false，**不 ack**
- 下一轮还要再 Claim

如果 SKIPPED 也写 `request_id`，UNIQUE 会挡住真正的 RUNNING INSERT。

规则：

```text
只有 RUNNING Claim 以及之后的终态更新可以带 request_id
SKIPPED / register / unregister → request_id 保持 NULL
```

`writeRuntime()` 应对 `status = SKIPPED` 丢掉 `request_id`，不能只判断 `<= 0`。

## 3.7 Duplicate Key 的业务语义

RunOnce UNIQUE 冲突不是系统故障，而是：

```text
RUN_ONCE_ALREADY_CLAIMED
```

当前 Worker：

```text
不重试 INSERT
不执行 Shell
不发送 HTTP
不创建 K8s Job
```

可记录普通信息级日志。

按已有行决定后续：

| 已有 Execution | 当前 Worker |
|---|---|
| RUNNING 且租约有效 | defer，不 ack |
| SUCCESS / TIMEOUT / CANCELLED / FAILED | ack，不执行 |
| RUNNING 且租约过期 | 统一 Recovery CAS；成功后 ack，失败则 defer |

## 3.8 Ack 的职责与「失败是否重跑」

`cron_task_run_request.consumed_at` 与 Execution UNIQUE 的职责必须分开：

```text
Execution UNIQUE
    ↓
谁获得执行权

consumed_at
    ↓
Request 是否已完成消费
```

Ack 失败不能重新获得执行权。

```text
Execution 创建成功
↓
Shell 成功
↓
ackRunOnce() 失败
↓
下一轮再次发现 pending
↓
再次 INSERT Execution
↓
UNIQUE 冲突
↓
precheck / claim 看到已有终态 → 只 ack，不再执行
```

因此：**Ack 不是幂等边界，Execution UNIQUE 才是最终幂等边界。**

这会改变现网一处行为，必须写进验收，而不是 silently 丢掉：

- 现网：Lease Recovery 把 RunOnce 标 FAILED **不会** ack；`precheckRunOnce()` 对 FAILED 返回 `execute`，会再 INSERT 一条重跑
- UNIQUE 落地后：同一 `request_id` 不能再有第二条 Execution

本次 P0 采用 **at-most-once**：第一次 Claim 之后不再重跑。配套改动：

1. `precheckRunOnce()` 把 FAILED 与 SUCCESS / TIMEOUT / CANCELLED 一样视为 `ack`
2. Recovery 收尾 RunOnce 后，不在这里偷偷再触发一次执行；pending request 由下一轮 precheck/claim 看到终态后 ack
3. 不把 `request_id` 在 FAILED 时置 NULL（那会重新打开重复执行窗口）

若产品以后要「崩溃后重试」，另开需求：只能在 Recovery 成功且明确释放 Claim 之后进行，不在本次 P0。

---

# 4. 两个 P0 的统一执行模型

```text
RunOnce / Cron
      ↓
Create Execution
      ↓
DB Atomic Claim
      │          Cron：cron_scheduled_task_record UNIQUE(cron_id, scheduled_at)
      │          RunOnce：cron_task_log UNIQUE(request_id)
 ┌────┴────┐
成功       冲突
 │          │
 ▼          ▼
RUNNING    不执行
 │
 ▼
Shell / HTTP / K8s
 │
 ▼
Lease + Heartbeat
 │
 ▼
Terminal
```

Lease Recovery：

```text
Expired Execution
      ↓
读取 Lease Snapshot
      ↓
K8s 仅只读观察（可 DEFER）
      ↓
CAS(owner + lease_until + expiry)
      │
 ┌────┴────┐
成功       冲突
 │          │
 ▼          ▼
Recovery   STOP
 │          │
 ▼          ▼
允许后续   不 Kill / 不删 Job
处理
```

---

# 5. 事务边界

Lease Recovery 不需要大事务，单条 CAS UPDATE 即可。K8s Get / Delete 不得包进该事务。

RunOnce Execution Claim 就是这一次 INSERT。可以放在短事务中完成必要的 Execution 元数据写入，但**不能把 Shell / HTTP / Kubernetes 请求放在数据库事务中**。

正确：

```text
BEGIN
  INSERT Execution
COMMIT

Shell / HTTP / K8s
```

错误：

```text
BEGIN
  INSERT Execution
  HTTP Request
COMMIT
```

---

# 6. 代码修改范围

## ExecutionService

- `transition()` 保持给普通状态机使用
- 新增 Recovery 专用 CAS（带 `lease_owner` + `lease_until` + `lease_until < recovery_now`）
- `recoverRow()` / `recoverExpiredLeases()` / `precheckRunOnce()` 全部改走 Recovery CAS
- `insertExecution()` 识别 1062，返回 / 抛出 Claim 冲突
- SKIPPED 不写 `request_id`
- `precheckRunOnce()`：FAILED 视为 `ack`；过期 RUNNING 用 Recovery CAS，不再直接 `transition`

## ExecutionRuntimeGuard

- Recovery CAS 失败后不得 Kill
- Heartbeat 优先于 Recovery（非 P0 正确性边界，可同批改）
- 保持仅本节点 + `pid > 0` 才 SIGKILL

## KubernetesCrashRecovery

- `resolve()` 拆成只读观察
- DELETE Job 移到 CAS 成功之后
- DEFER 语义不变：不 CAS、不删 Job

## CronManager（swoolefy，本仓库 path 依赖）

- RunOnce RUNNING 写库失败不得再隔离后执行
- 推荐增加 `run_once_claim` 门闩，或让 `invokeLogWriter()` 对 Claim 冲突短路 `runExecutionPipeline()`
- `recordSkip()` 不再带 `request_id`

## WorkerCron conf

- `schedule_fork_conf.php` / `schedule_url_conf.php` / `schedule_k8s_conf.php`
- 若走门闩方案，增加 `run_once_claim`，与现有 `run_once_precheck` / `run_once_ack` / `schedule_slot_claim` 并列

## CronTaskService

- `ackRunOnce()` 职责不变：只写 `consumed_at`
- 不在 Service 里用 `SELECT + INSERT` 冒充 Claim

## Shell / HTTP / Kubernetes Executor

不改变业务模型，只保证它们只能从已经成功 Claim 的 Execution 进入。

---

# 7. Migration 实施

## 7.1 历史数据检查

```sql
SELECT request_id, COUNT(*) AS cnt
FROM cron_task_log
WHERE request_id IS NOT NULL
  AND request_id > 0
GROUP BY request_id
HAVING COUNT(*) > 1;
```

确认没有历史重复后，再增加 UNIQUE。

```sql
SELECT COUNT(*)
FROM cron_task_log
WHERE request_id = 0;
```

如果存在：

```sql
UPDATE cron_task_log
SET request_id = NULL
WHERE request_id = 0;
```

SKIPPED 行若已带 `request_id`，加 UNIQUE 前也要清掉，否则会挡住后续真正 Claim：

```sql
UPDATE cron_task_log
SET request_id = NULL
WHERE status = 4
  AND request_id IS NOT NULL;
```

`status = 4` 即 `ExecutionStatus::SKIPPED`。若同一 `request_id` 已有终态行，SKIPPED 行必须让路。

## 7.2 增加 UNIQUE

```sql
ALTER TABLE cron_task_log
DROP INDEX idx_request_id,
ADD UNIQUE KEY uk_request_id (request_id);
```

Lease Recovery 不需要新增字段，现有 `lease_owner`、`lease_until` 已足够完成 CAS。也不新增 Lease Version。

UNIQUE 与 CronManager Claim 短路必须同批发布。只加索引、不改写库隔离，输家仍会执行。

---

# 8. 测试方案

## 8.1 Lease Recovery：Heartbeat 与 Recovery 竞态

Recovery 读取：

```text
owner = worker-A
lease_until = T
```

随后 Heartbeat 更新 Lease：

```text
lease_until = T + 30s
```

Recovery 使用旧快照执行 CAS：

```sql
AND lease_owner = 'worker-A'
AND lease_until = T
AND lease_until < recovery_now
```

预期：

```text
affected_rows = 0
Execution 仍然 RUNNING
不执行 SIGKILL
不删除 Kubernetes Job
```

## 8.2 Lease Recovery：真正过期

没有 Heartbeat，且：

```text
lease_until < recovery_now
```

预期：

```text
affected_rows = 1
Recovery 成功
本节点 pid>0 才允许 SIGKILL
```

## 8.3 Lease Recovery：Owner 变化

旧 Owner 为 A，数据库已变成 B。旧 CAS 必须失败。

## 8.4 Lease Recovery：Lease 时间变化

旧 `lease_until=T`，Heartbeat 后变成 `T+30s`。旧 CAS 必须失败。

## 8.5 K8s DEFER 与误删

Job 仍 Active 或 API 不可达：本轮不 CAS、不删 Job。

`CANCEL_REQUESTED` + Job Active：必须 CAS 成功后才 DELETE。Heartbeat 续租成功时，不得删 Job。

## 8.6 RunOnce 双 Agent 并发

同时让两个 Agent 处理同一个 `request_id=1001`。

预期：

```text
Execution = 1
实际 Shell / HTTP / K8s = 1
```

一个 INSERT 成功，另一个 Duplicate Key，且输家不得因 logWriter 吞异常而继续执行。

## 8.7 Ack 丢失

```text
Execution 创建成功
↓
真实执行成功
↓
ack 失败
↓
下一轮重新发现 Request
```

预期再次 INSERT 时 UNIQUE 冲突，只补 ack，不能再次产生真实副作用。

## 8.8 SKIPPED 后再消费

时间窗 / 重叠导致 SKIPPED 后，请求仍 pending。下一轮必须还能 Claim 并执行一次。SKIPPED 行不得占用 `request_id`。

## 8.9 普通 Cron

验证多个普通 Execution 的：

```text
request_id = NULL
```

可以同时存在，不受 UNIQUE 影响。

## 8.10 K8s RunOnce

两个 Agent 并发 RunOnce 时必须满足：

```text
Execution = 1
Kubernetes Job = 1
```

## 8.11 崩溃 Recovery 后不再重跑（行为变更）

RunOnce 已 Claim，Worker 崩溃，Recovery CAS 成功标 FAILED。下一轮 pending 仍在。

预期：precheck/claim 看到终态 FAILED → 只 ack，不再创建第二条 Execution，不再打 Shell / HTTP / K8s。

---

# 9. Metrics 与日志

可增加轻量指标：

```text
cron_execution_lease_recovery_total
cron_execution_lease_recovery_cas_conflict_total
cron_run_once_claim_total
cron_run_once_claim_conflict_total
```

CAS Conflict 和 RunOnce Claim Conflict 都是正常并发信号，不应默认打成 ERROR。

建议日志：

```text
execution lease recovery cas conflict
execution_id=100
```

以及：

```text
run once execution already claimed
request_id=1001
```

---

# 10. 不在本次 P0 范围内

本次不做：

- Scheduler Leader
- Redis Lock
- MQ
- Execution 状态机大重构
- 新增 Lease Version 字段
- PID 管理机制重构
- Shell / HTTP / K8s 新功能
- Alert 重构
- Dashboard 优化
- Cron 表达式优化
- RunOnce 崩溃后自动重试（UNIQUE 落地后明确不做）

目标只有两个：**修复 Lease Recovery 并发错误、修复 RunOnce 重复执行。**

---

# 11. 实施顺序

```text
1. 检查 request_id 历史重复 / 0 值 / SKIPPED 占用
        ↓
2. 清洗 request_id = 0，以及会挡住 Claim 的 SKIPPED.request_id
        ↓
3. 改 insertExecution + CronManager Claim 短路（或 run_once_claim 门闩）
        ↓
4. SKIPPED 不再写 request_id
        ↓
5. 增加 UNIQUE(request_id)（与 3 同批发布）
        ↓
6. 修改 Lease Recovery CAS
        ↓
7. K8s：只读观察 → CAS → 成功才 DELETE
        ↓
8. CAS 失败禁止 Kill / 删 Job
        ↓
9. RuntimeGuard Heartbeat → Recovery
        ↓
10. precheckRunOnce 统一 Recovery CAS，FAILED 改为 ack
        ↓
11. 并发测试
        ↓
12. 灰度观察 CAS Conflict 与重复 Execution
```

---

# 12. 最终验收标准

## Lease Recovery

- Recovery 使用 `lease_owner` CAS
- Recovery 使用 `lease_until` CAS
- Recovery 使用 `lease_until < recovery_now`
- CAS 失败不执行 SIGKILL
- CAS 失败不删除 Kubernetes Job
- K8s Active / API 不可达仍 DEFER，不误标终态
- Heartbeat 与 Recovery 并发不会误恢复
- Owner 变化不会误恢复
- Lease 续租不会误恢复
- 真正过期的 Execution 仍能正常 Recovery
- `precheckRunOnce()` 使用相同 Recovery CAS 语义
- `lease_owner` 不写成 NULL

## RunOnce Dedup

- `cron_task_log.request_id` 建立 UNIQUE，类型仍是 `bigint unsigned NULL`
- 普通 Execution / SKIPPED 的 `request_id` 为 NULL
- RunOnce RUNNING INSERT 是唯一 Claim
- Duplicate Key 后禁止执行，且不能被 `writeLog` 隔离掉
- Execution 创建成功后才能 Shell / HTTP / K8s
- Ack 丢失不会重复执行
- SKIPPED 后下一轮仍能执行恰好一次
- 多 Agent 并发最终只有一个 Execution
- 多 Agent 并发实际副作用只有一次
- Recovery 后的 FAILED RunOnce 只 ack，不再重跑

---

# 13. 结论

这两个问题都属于当前 schedule-job 的**执行正确性 P0**。对照代码后，原方向成立，但有几处必须按现状修正：

1. Recovery 不能只加判断，要把 `id + status` 升级为 Lease Snapshot CAS；K8s 必须先只读观察，DEFER 时不 CAS，DELETE 只能在 CAS 成功之后。
2. `lease_owner` 不能 SET NULL。
3. RunOnce 的修复重点不是继续优化 `ackRunOnce()`，而是 `UNIQUE(request_id)` + **Claim 失败必须挡住执行器**。现网 `invokeLogWriter()` 吞异常，只加索引不够。
4. `request_id` 已经是可空 BIGINT，不要改类型，也不要按空串清洗。
5. SKIPPED 不能占用 `request_id`，否则时间窗跳过后再也 Claim 不了。
6. UNIQUE 落地后 RunOnce 从「Recovery 后再跑一次」变为 at-most-once，`precheckRunOnce()` 必须把 FAILED 当 ack，避免请求永远 pending。

最终边界：

```text
RunOnce：谁获得执行权？
        ↓
UNIQUE(request_id) 且 Claim 成功才进入执行器

Execution：谁拥有运行中的任务？
        ↓
Lease Owner + Lease Until + Heartbeat + Recovery CAS
```

最终目标：

```text
一个 RunOnce Request
        ↓
最多一个带 request_id 的 Execution
        ↓
最多一次真实执行
```

以及：

```text
一个 Expired Execution
        ↓
只有 CAS 成功的 Recovery 才能恢复
        ↓
并发 Heartbeat / Lease 更新不会被旧 Recovery 覆盖
        ↓
CAS 失败不会 Kill 进程、不会删 K8s Job
```
