# schedule-job P0 生产可靠性优化技术方案

> 项目：schedule-job  
> 基础框架：Swoolefy  
> 优化范围：Execution Lease / RunOnce Dedup / Timeout + Cancel  
> 优先级：P0  
> 目标：补齐 Cron Execution 生命周期可靠性，不重构现有 Cron Scheduler。

---

## 1. 背景

`schedule-job` 当前已经具备：

- Cron Expression 调度
- Agent 与任务动态同步
- Runtime Diff
- RunOnce
- Shell / HTTP 执行
- Retry
- Execution Log
- Agent 心跳
- RBAC / 操作审计
- Runtime Overview

当前主要缺口已经不是“任务能否被调度”，而是：

> Worker / Process 异常退出以后，Execution 的生命周期能否正确闭合。

核心问题：

```text
① Worker Crash
        ↓
RUNNING Execution 永久残留

② RunOnce
        ↓
业务已经成功
        ↓
Worker 在 ACK 前 Crash
        ↓
RunOnce 再执行一次

③ Process Hang
        ↓
任务无限运行
        ↓
无法自动停止
        ↓
无法人工 Cancel
```

因此本阶段只解决 Execution Reliability，不修改 Cron Expression、Scheduler、Runtime Diff 等已有核心调度逻辑。

---

# 2. 设计目标

最终 Execution 生命周期必须具备：

```text
                     ┌──────────────┐
                     │   SCHEDULE   │
                     └──────┬───────┘
                            ↓
                     ┌──────────────┐
                     │   PENDING    │
                     └──────┬───────┘
                            ↓
                     ┌──────────────┐
                     │   RUNNING    │
                     │              │
                     │ Lease Active │
                     └──────┬───────┘
                            │
              ┌─────────────┼──────────────┐
              ↓             ↓              ↓
           SUCCESS        FAILED        TIMEOUT

RUNNING
   ↓
CANCEL_REQUESTED
   ↓
CANCELLED

RUNNING
   ↓
Worker Crash
   ↓
Lease Expired
   ↓
Recovery
   ↓
FAILED / RETRY
```

核心目标：

> 任何 Execution 最终都必须进入一个可解释的终态。

---

# 3. 非目标

本阶段明确不做：

```text
❌ Redis Scheduler
❌ Kafka / RabbitMQ 调度
❌ Leader Election
❌ Raft
❌ 分布式锁重构
❌ DAG
❌ Workflow
❌ 分布式 Exactly Once
❌ 跨数据中心调度
❌ 全新 Scheduler
```

保持现有架构：

```text
MySQL
  +
Swoolefy Runtime
  +
Cron Agent
```

MySQL 继续作为持久化事实源，Swoolefy Runtime 继续负责实际调度。

---

# 4. 总体架构

```text
                         Admin
                           │
                           ↓
                      cron_task
                           │
                           ↓
                    Cron Task Agent
                           │
                           ↓
                    CronManager
                           │
                           ↓
                    CronScheduler
                           │
                           ↓
                    ExecutionService
                           │
              ┌────────────┼────────────┐
              ↓            ↓            ↓
           Shell         HTTP         Local
              │            │            │
              └────────────┼────────────┘
                           ↓
                    Execution Record
                           │
               ┌───────────┼───────────┐
               ↓           ↓           ↓
             Lease       Timeout     Cancel
               │           │           │
               └───────────┼───────────┘
                           ↓
                         MySQL
```

核心分层：

```text
Cron Scheduler
    =
什么时候执行

Execution
    =
这一次执行是什么

Lease
    =
谁拥有这次执行

Process Controller
    =
如何控制 / 结束进程
```

---

# 5. 核心模型

## 5.1 Task

表示：

> 应该执行什么。

对应：

```text
cron_task
```

包含：

```text
cron_id
name
expression
command
exec_type
status
retry
timeout
...
```

---

## 5.2 Execution

表示：

> 这一次实际执行是什么。

建议继续以 `cron_task_log` 作为当前 Execution 持久化记录，并逐步明确其 Execution 语义。

核心字段：

```text
execution_id
cron_id
trigger_type
request_id
attempt
status
pid
started_at
finished_at
...
```

---

## 5.3 Lease

表示：

> 哪个 Worker 当前拥有这次 Execution。

核心字段：

```text
lease_owner
lease_until
heartbeat_at
```

---

## 5.4 RunOnce Request

表示：

> 管理员要求立即执行一次任务。

核心字段：

```text
request_id
cron_id
requested_at
consumed_at
```

---

# 6. Execution 状态机

建议统一状态：

```text
PENDING
   ↓
RUNNING
   ↓
┌───────────────┬──────────────┬──────────────┐
↓               ↓              ↓
SUCCESS        FAILED        TIMEOUT

RUNNING
   ↓
CANCEL_REQUESTED
   ↓
CANCELLED
```

Retry 不应该把历史 Execution：

```text
FAILED → RUNNING
```

而应该：

```text
Attempt 1
   ↓
FAILED
   ↓
new attempt
   ↓
Attempt 2
   ↓
RUNNING
```

这样历史状态不可变，Execution 历史可追溯。

---

# 7. 状态转换 CAS

所有 Execution 状态更新必须统一走 CAS。

建议：

```php
ExecutionService::transition(
    int $executionId,
    int $fromStatus,
    int $toStatus,
    array $extra = []
): bool
```

对应 SQL：

```sql
UPDATE cron_task_log
SET
    status = :to_status,
    ...
WHERE id = :id
  AND status = :from_status;
```

判断：

```text
affected_rows == 1
```

才表示状态转换成功。

合法转换：

```text
PENDING → RUNNING

RUNNING → SUCCESS
RUNNING → FAILED
RUNNING → TIMEOUT
RUNNING → CANCEL_REQUESTED

CANCEL_REQUESTED → CANCELLED
```

禁止：

```text
SUCCESS → RUNNING
SUCCESS → FAILED
FAILED → SUCCESS
CANCELLED → SUCCESS
```

---

# 8. Execution Lease

## 8.1 问题

Worker Runtime 状态属于进程内。

例如：

```text
Worker A
   ↓
Execution #100
   ↓
RUNNING
   ↓
Worker Crash
```

内存状态消失，但数据库可能仍然：

```text
status = RUNNING
```

最终形成永久 RUNNING。

---

## 8.2 Lease 字段

建议在 `cron_task_log` 增加：

```sql
ALTER TABLE cron_task_log
ADD COLUMN lease_owner varchar(128) NOT NULL DEFAULT ''
    COMMENT 'Execution Lease owner',

ADD COLUMN lease_until datetime NULL
    COMMENT 'Lease expiration time',

ADD COLUMN heartbeat_at datetime NULL
    COMMENT 'Execution heartbeat time';
```

---

## 8.3 Lease 参数

推荐：

```php
'execution_lease' => [
    'enabled' => true,
    'duration' => 60,
    'heartbeat_interval' => 15,
    'recovery_interval' => 30,
    'startup_recovery' => true,
]
```

原则：

```text
heartbeat_interval < lease_duration / 2
```

例如：

```text
15s < 60s / 2
```

---

# 9. Worker Identity

Lease Owner 不建议只使用 hostname。

建议：

```text
node_id
+
worker_pid
+
boot_id
```

最终：

```text
lease_owner =
node_id:worker_pid:boot_id
```

例如：

```text
node-01:18273:8f71...
```

Worker 重启后：

```text
node-01:18273:OLD
```

与：

```text
node-01:19382:NEW
```

一定不同。

---

# 10. Lease 获取

Execution：

```text
PENDING
   ↓
RUNNING
```

同时建立 Lease。

SQL：

```sql
UPDATE cron_task_log
SET
    status = :running,
    lease_owner = :owner,
    lease_until = :lease_until,
    heartbeat_at = NOW(),
    started_at = NOW()
WHERE id = :execution_id
  AND status = :pending;
```

只有：

```text
affected_rows = 1
```

才能认为 Worker 成功获得 Execution。

并发 Worker 中其他 Worker 必须放弃。

---

# 11. Lease Heartbeat

Worker 执行期间周期性续租：

```sql
UPDATE cron_task_log
SET
    heartbeat_at = NOW(),
    lease_until = DATE_ADD(NOW(), INTERVAL 60 SECOND)
WHERE id = :execution_id
  AND status = :running
  AND lease_owner = :owner;
```

关键条件：

```text
status = RUNNING
AND lease_owner = current worker
```

防止旧 Worker 在 Execution 被 Recovery 后继续更新。

---

# 12. Lease Expiration

判断：

```text
status = RUNNING
AND lease_until < NOW()
```

表示：

```text
Lease Expired
```

通常意味着：

```text
Worker Crash
Worker 停止
Worker 网络异常
Heartbeat 长时间失败
```

---

# 13. Crash Recovery

Worker 启动时执行 Recovery：

```text
Agent Start
    ↓
Recovery
    ↓
查询 RUNNING
    ↓
lease_until < NOW()
    ↓
Recovery
```

查询：

```sql
SELECT id
FROM cron_task_log
WHERE status = :running
  AND lease_until < NOW()
LIMIT 100;
```

Recovery 必须使用 CAS：

```sql
UPDATE cron_task_log
SET
    status = :failed,
    failure_reason = 'WORKER_CRASH',
    finished_at = NOW()
WHERE id = :execution_id
  AND status = :running
  AND lease_until < NOW();
```

避免正常 Worker 与 Recovery Worker 同时修改状态。

---

# 14. Crash Recovery 与 Retry

旧 Execution 不应该：

```text
RUNNING → RUNNING
```

也不建议直接：

```text
RUNNING → PENDING
```

应该保留历史：

```text
Attempt 1
status = FAILED
failure_reason = WORKER_CRASH
```

如果允许 Retry：

```text
Attempt 2
status = PENDING
```

最终：

```text
Attempt 1 → FAILED / WORKER_CRASH
Attempt 2 → SUCCESS
```

---

# 15. Recovery Race Condition

场景：

```text
Worker A
    ↓
Heartbeat

Worker B
    ↓
Recovery
```

Heartbeat 必须：

```sql
WHERE
    status = RUNNING
    AND lease_owner = A
```

Recovery 必须：

```sql
WHERE
    status = RUNNING
    AND lease_until < NOW()
```

两者最终只能有一个状态转换成功。

---

# 16. Failure Reason

建议增加：

```text
failure_reason
```

至少支持：

```text
WORKER_CRASH
LEASE_EXPIRED
TIMEOUT
PROCESS_EXIT_ERROR
SIGNAL_TERMINATED
CANCELLED
EXECUTION_ERROR
```

Dashboard 不应该只显示：

```text
FAILED
```

而应该显示：

```text
FAILED
Reason: WORKER_CRASH
```

---

# 17. RunOnce Dedup

## 17.1 当前问题

存在经典窗口：

```text
RunOnce Request
      ↓
Execution
      ↓
业务 SUCCESS
      ↓
Worker Crash
      X
ACK
```

数据库：

```text
request = pending
execution = SUCCESS
```

Worker 重启以后如果没有 Dedup：

```text
Request
   ↓
再次 Execution
```

导致重复执行。

---

# 18. request_id

`cron_task_run_request` 增加：

```sql
request_id varchar(64) NOT NULL
```

并建立：

```sql
UNIQUE KEY uk_request_id (request_id)
```

如果客户端没有传：

```text
Server Generate UUID
```

---

# 19. Execution 关联 request_id

`cron_task_log` 增加：

```sql
request_id varchar(64) NOT NULL DEFAULT ''
```

形成：

```text
RunOnce Request
       │
       │ request_id
       ↓
Execution
```

---

# 20. Dedup 规则

核心规则：

> 一个 `request_id` 最多对应一个 Execution。

第一次：

```text
request_id = R1001
       ↓
INSERT Execution
```

第二次：

```text
request_id = R1001
       ↓
UNIQUE conflict
       ↓
查询已有 Execution
```

不得创建第二条 Execution。

---

# 21. RunOnce 正确流程

```text
                    RunOnce
                       │
                       ↓
                request_id = R1
                       │
                       ↓
               查询已有 Execution
                       │
                ┌──────┴──────┐
                ↓             ↓
              exists        not exists
                │             │
                ↓             ↓
             reuse        create
                │             │
                └──────┬──────┘
                       ↓
                    execute
                       │
             ┌─────────┼─────────┐
             ↓         ↓         ↓
          SUCCESS    FAILED    RUNNING
             │
             ↓
            ACK
```

---

# 22. ACK 顺序

正确：

```text
① 创建 / 获取 Execution
② Execution 执行
③ 写 Execution 最终状态
④ ACK RunOnce Request
```

禁止：

```text
① ACK
② Execution
```

否则可能：

```text
ACK 成功
Worker Crash
Execution 未执行
```

造成任务丢失。

---

# 23. ACK CAS

```sql
UPDATE cron_task_run_request
SET
    consumed_at = NOW()
WHERE request_id = :request_id
  AND consumed_at IS NULL;
```

只有：

```text
affected_rows = 1
```

才表示 ACK 成功。

---

# 24. RunOnce Crash 场景

## Case A：创建 Execution 前 Crash

```text
Request pending
Execution 不存在
```

重启：

```text
创建 Execution
```

正常。

---

## Case B：Execution 创建后 Crash

```text
Request pending
Execution exists
```

重启：

```text
request_id lookup
        ↓
已有 Execution
        ↓
检查状态
```

如果：

```text
SUCCESS
```

直接：

```text
ACK
```

如果：

```text
RUNNING + lease valid
```

不能重复执行。

如果：

```text
RUNNING + lease expired
```

进入 Recovery。

---

## Case C：Execution SUCCESS，ACK 前 Crash

重启：

```text
Request pending
Execution SUCCESS
```

直接 ACK。

不会再次创建 Execution。

---

# 25. Timeout

## 25.1 Lease 与 Timeout 的区别

必须明确：

```text
Lease
=
Worker 是否还活着
```

而：

```text
Timeout
=
业务 Execution 是否允许继续运行
```

例如：

```text
timeout = 300s
```

表示无论 Worker 是否正常：

```text
超过 300s
```

都必须终止任务。

---

# 26. Timeout 字段

建议增加：

```sql
ALTER TABLE cron_task_log
ADD COLUMN timeout_at datetime NULL
    COMMENT 'Execution timeout deadline',

ADD COLUMN cancelled_at datetime NULL
    COMMENT 'Execution cancel time';
```

Execution 创建：

```text
timeout_at =
started_at + timeout
```

---

# 27. Timeout 流程

```text
RUNNING
   ↓
timeout_at reached
   ↓
SIGTERM
   ↓
Grace Period
   ↓
process exited?
   ├── YES → TIMEOUT
   │
   └── NO
          ↓
       SIGKILL
          ↓
       TIMEOUT
```

不能只修改数据库：

```text
RUNNING → TIMEOUT
```

因为 OS Process 可能仍然运行。

必须先完成进程终止，再完成最终状态闭合。

---

# 28. Signal 策略

推荐：

```text
SIGTERM
   ↓
grace_period = 10s
   ↓
SIGKILL
```

配置：

```php
'execution' => [
    'timeout' => 300,
    'terminate_grace_period' => 10,
]
```

优先：

```text
SIGTERM
```

允许应用进行：

```text
finally
flush
close connection
cleanup
```

只有无法退出时：

```text
SIGKILL
```

---

# 29. Process Controller

底层 `CronForkRunner` 已经负责：

```text
proc_open
PID
stdout
stderr
process lifecycle
```

不要重新实现 Process Runner。

建议增加：

```text
ExecutionController
```

职责：

```text
Execution
    ↓
PID
    ↓
Timeout
    ↓
Cancel
    ↓
Signal
```

分层：

```text
CronManager
    ↓
ExecutionService
    ↓
ExecutionController
    ↓
CronForkRunner
```

职责分别为：

```text
CronManager
=
什么时候执行

ExecutionService
=
这次 Execution 的生命周期

ExecutionController
=
进程何时需要终止

CronForkRunner
=
如何启动 / 监控进程
```

---

# 30. Cancel

Cancel 与 Timeout 类似。

区别：

```text
Timeout
=
系统自动终止

Cancel
=
用户主动终止
```

推荐状态：

```text
RUNNING
   ↓
CANCEL_REQUESTED
   ↓
SIGTERM
   ↓
Grace Period
   ↓
SIGKILL
   ↓
CANCELLED
```

---

# 31. 为什么需要 CANCEL_REQUESTED

如果没有中间状态：

```text
Admin Cancel
```

同时：

```text
Process SUCCESS
```

容易发生：

```text
CANCELLED
SUCCESS
```

互相覆盖。

因此先：

```text
RUNNING
   ↓
CANCEL_REQUESTED
```

然后由 Agent 真正停止进程。

---

# 32. Cancel API

新增：

```http
POST /api/cron/executions/{id}/cancel
```

响应：

```json
{
    "execution_id": 1001,
    "status": "CANCEL_REQUESTED"
}
```

API 不直接发送 SIGTERM。

API 只负责：

```text
DB State Transition
```

真正的进程操作由 Agent 完成。

---

# 33. Cancel CAS

```sql
UPDATE cron_task_log
SET
    status = :cancel_requested,
    cancelled_at = NOW()
WHERE id = :execution_id
  AND status = :running;
```

如果：

```text
affected_rows = 1
```

表示 Cancel Request 被接受。

如果：

```text
affected_rows = 0
```

说明 Execution 已经结束，返回：

```text
already_finished
```

---

# 34. Cancel Worker

Agent 发现：

```text
CANCEL_REQUESTED
```

找到当前 Runtime Execution：

```text
PID
```

执行：

```text
SIGTERM
```

等待：

```text
grace_period
```

仍然存在：

```text
SIGKILL
```

最终：

```text
CANCELLED
```

---

# 35. PID 安全

不能简单：

```php
posix_kill($pid, SIGTERM);
```

必须确保：

```text
PID
+
Execution
+
lease_owner
```

属于当前 Agent 创建的 Execution。

Runtime 可以维护：

```php
$runningExecutions[$executionId] = [
    'pid' => $pid,
    'owner' => $leaseOwner,
];
```

数据库：

```text
pid
lease_owner
```

作为持久化辅助。

---

# 36. Timeout / Cancel Race

## Race 1：Success vs Timeout

同时：

```text
Execution SUCCESS
```

和：

```text
Timeout Checker
```

必须通过：

```sql
WHERE status = RUNNING
```

保证只有一个转换成功。

最终只能是：

```text
SUCCESS
```

或者：

```text
TIMEOUT
```

---

## Race 2：Success vs Cancel

Cancel：

```text
RUNNING → CANCEL_REQUESTED
```

Success：

```text
RUNNING → SUCCESS
```

只有一个成功。

如果 Cancel 先成功：

```text
CANCEL_REQUESTED
```

后续不能直接：

```text
SUCCESS
```

---

# 37. ExecutionService

建议增加统一服务：

```text
ExecutionService
```

职责：

```text
create()
start()
heartbeat()
success()
failure()
timeout()
cancel()
recover()
ackRunOnce()
```

不要让：

```text
Controller
CronManager
CronTaskService
CronTaskManagerService
CronForkRunner
```

各自直接修改：

```text
cron_task_log.status
```

所有状态转换统一进入：

```text
ExecutionService
```

---

# 38. 推荐代码结构

```text
App/Module/Cron/
│
├── Service/
│   ├── CronTaskService.php
│   ├── CronTaskManagerService.php
│   │
│   ├── ExecutionService.php
│   ├── ExecutionLeaseService.php
│   ├── ExecutionRecoveryService.php
│   ├── ExecutionTimeoutService.php
│   └── RunOnceService.php
│
├── Repository/
│   ├── CronTaskRepository.php
│   ├── CronTaskLogRepository.php
│   └── CronTaskRunRequestRepository.php
│
├── DTO/
│   ├── ExecutionDto.php
│   ├── ExecutionLeaseDto.php
│   └── RunOnceRequestDto.php
│
└── Enum/
    ├── ExecutionStatus.php
    └── FailureReason.php
```

---

# 39. CronManager 不负责什么

不要让 `CronManager` 最终变成：

```text
CronManager
 ├── Cron Parser
 ├── DB Repository
 ├── Lease
 ├── Recovery
 ├── Timeout
 ├── Cancel
 ├── RunOnce
 ├── PID
 ├── Retry
 └── Notification
```

`CronManager` 应继续保持：

```text
Cron Expression
Runtime Job
Next Run
Trigger
```

Execution Reliability 放到 Execution 层。

---

# 40. Metrics

必须增加：

## Lease

```text
schedule_job_execution_lease_expired_total

schedule_job_execution_recovered_total

schedule_job_execution_heartbeat_failed_total
```

## Dedup

```text
schedule_job_run_once_dedup_total

schedule_job_run_once_ack_total

schedule_job_run_once_duplicate_total
```

## Timeout

```text
schedule_job_execution_timeout_total

schedule_job_execution_force_kill_total
```

## Cancel

```text
schedule_job_execution_cancel_total

schedule_job_execution_cancel_success_total

schedule_job_execution_cancel_force_kill_total
```

---

# 41. Execution 日志

统一记录：

```text
execution_id
cron_id
request_id
attempt
node_id
lease_owner
pid
```

事件：

```text
LEASE_ACQUIRED
LEASE_HEARTBEAT
LEASE_EXPIRED
RECOVERED
STARTED
SUCCESS
FAILED
TIMEOUT
CANCEL_REQUESTED
CANCELLED
FORCE_KILLED
```

例如：

```text
[Execution]
id=1001
cron=10
attempt=1
event=LEASE_EXPIRED
reason=WORKER_CRASH
```

---

# 42. Dashboard

增加：

```text
Executions
────────────────
Running
Success
Failed
Timeout
Cancelled

Crash Recovery
────────────────
Lease Expired
Recovered

RunOnce
────────────────
Total
Duplicate Prevented

Timeout
────────────────
Timeout Count
Force Kill Count

Cancel
────────────────
Cancel Count
Force Kill Count
```

Execution Detail：

```text
Execution #1001

Status       RUNNING
Attempt      1
PID          18273

Started At   10:00:00
Timeout At   10:05:00

Lease Owner  node01:18273:xxx
Lease Until  10:01:00
Heartbeat    10:00:45
```

---

# 43. 测试方案

本次改造不能只做 Unit Test。

必须覆盖：

```text
Unit
+
Coroutine
+
Integration
+
Crash Simulation
```

---

# 44. Lease Unit Test

### Test 1：PENDING → RUNNING

验证：

```text
affected_rows = 1
lease_owner 正确
lease_until 正确
```

### Test 2：两个 Worker 同时 Acquire

```text
Worker A
Worker B
```

期望：

```text
A = success
B = conflict
```

### Test 3：Heartbeat

正确 owner 更新成功。

### Test 4：错误 owner

Worker B 不能续租 Worker A 的 Execution。

---

# 45. Crash Recovery Test

模拟：

```text
Worker A
 ↓
Execution RUNNING
 ↓
kill -9
```

等待：

```text
lease expired
```

启动 Worker B。

期望：

```text
RUNNING
 ↓
FAILED
```

并且：

```text
failure_reason = WORKER_CRASH
```

---

# 46. Recovery Race Test

同时：

```text
Worker A heartbeat
```

与：

```text
Worker B recovery
```

重复：

```text
1000+
```

期望：

```text
最终只能有一个状态转换成功。
```

---

# 47. RunOnce Dedup Test

同一个：

```text
request_id
```

同时发送：

```text
1000 次
```

期望：

```text
Execution = 1
```

---

# 48. RunOnce Crash Test

模拟：

```text
Execution SUCCESS
 ↓
Worker Crash
 ↓
ACK 未执行
```

Worker Restart：

```text
Request pending
Execution SUCCESS
```

期望：

```text
Execution = 1
Request = ACK
```

绝对不能产生第二次 Execution。

---

# 49. Timeout Test

创建：

```text
sleep 60
```

配置：

```text
timeout = 2s
```

期望：

```text
2s
 ↓
SIGTERM
 ↓
Grace Period
 ↓
SIGKILL
 ↓
TIMEOUT
```

并验证：

```text
OS Process 已不存在
```

---

# 50. Cancel Test

创建：

```text
sleep 60
```

执行：

```text
Cancel API
```

期望：

```text
RUNNING
 ↓
CANCEL_REQUESTED
 ↓
SIGTERM
 ↓
CANCELLED
```

---

# 51. Cancel Race Test

同时：

```text
Process SUCCESS
```

和：

```text
Cancel
```

重复：

```text
1000 次
```

最终只能：

```text
SUCCESS
```

或者：

```text
CANCELLED
```

不能出现非法状态覆盖。

---

# 52. Timeout Race Test

同时：

```text
Timeout Checker
```

和：

```text
Execution Success
```

执行。

要求：

```text
最终只有一个状态转换成功。
```

---

# 53. 回归测试

现有功能必须全部保持：

```text
Cron Expression
✔

Dynamic Task
✔

Enable / Disable
✔

Runtime Diff
✔

RunOnce
✔

Retry
✔

Overlap
✔

Shell
✔

HTTP
✔

Execution Log
✔

Heartbeat
✔

RBAC
✔
```

新增：

```text
Execution CAS
✔

Execution Lease
✔

Crash Recovery
✔

RunOnce Dedup
✔

Timeout
✔

Cancel
✔
```

---

# 54. 实施顺序

不要三个功能同时修改。

推荐：

```text
P0.1
Execution State + CAS
        ↓
P0.2
Execution Lease
        ↓
P0.3
Crash Recovery
        ↓
P0.4
RunOnce Dedup
        ↓
P0.5
Timeout
        ↓
P0.6
Cancel
        ↓
P0.7
Metrics
        ↓
P0.8
Integration Test
```

---

# 55. 第一阶段：Execution CAS

先建立统一：

```text
ExecutionService
```

目标：

```text
所有 Execution status 修改
        ↓
统一入口
        ↓
CAS
```

这是 Lease / Recovery / Timeout / Cancel 的共同基础。

---

# 56. 第二阶段：Lease + Recovery

增加：

```text
lease_owner
lease_until
heartbeat_at
```

实现：

```text
acquire
heartbeat
expire
recover
```

重点完成：

```text
Worker Crash Test
```

---

# 57. 第三阶段：RunOnce Dedup

增加：

```text
request_id
```

建立：

```text
UNIQUE(request_id)
```

调整：

```text
RunOnce
 ↓
Execution
 ↓
ACK
```

确保：

```text
Execution SUCCESS
+
ACK Crash
```

不会产生重复执行。

---

# 58. 第四阶段：Timeout

增加：

```text
timeout
timeout_at
```

实现：

```text
Timeout Checker
 ↓
SIGTERM
 ↓
Grace Period
 ↓
SIGKILL
```

---

# 59. 第五阶段：Cancel

实现：

```text
RUNNING
 ↓
CANCEL_REQUESTED
 ↓
SIGTERM
 ↓
SIGKILL
 ↓
CANCELLED
```

新增：

```text
POST /api/cron/executions/{id}/cancel
```

---

# 60. 最终架构

```text
                        schedule-job
                             │
                             ↓
                       Cron Scheduler
                             │
                             ↓
                         Execution
                             │
              ┌──────────────┼──────────────┐
              ↓              ↓              ↓
           State           Lease          Dedup
             │               │              │
             ↓               ↓              ↓
            CAS          Heartbeat       request_id
             │               │              │
             └───────────────┼──────────────┘
                             ↓
                           MySQL
                             │
                ┌────────────┼────────────┐
                ↓            ↓            ↓
             Recovery      Timeout       Cancel
                │            │            │
                ↓            ↓            ↓
             Crash        SIGTERM       SIGTERM
                │            │            │
                ↓            ↓            ↓
             Retry         SIGKILL       SIGKILL
```

---

# 61. 最终验收标准

## Execution Lease

```text
✔ Worker Crash 后 RUNNING 不永久残留
✔ Lease 自动过期
✔ Recovery 可以发现 stale Execution
✔ Recovery 使用 CAS
✔ 旧 Worker 不能修改已经被接管的 Execution
✔ Worker Restart 可以恢复 Execution 状态
```

## RunOnce Dedup

```text
✔ 一个 request_id 最多一个 Execution
✔ 100 Coroutine 同时 RunOnce 仍然只有一个 Execution
✔ Execution SUCCESS 后 ACK Crash 不会重复执行
✔ pending request 可以恢复
✔ 已完成 Execution 可以重新识别
```

## Timeout

```text
✔ timeout 可配置
✔ timeout_at 持久化
✔ 自动 SIGTERM
✔ Grace Period
✔ 必要时 SIGKILL
✔ 最终状态 TIMEOUT
✔ 不产生 Zombie Process
```

## Cancel

```text
✔ Admin 可以 Cancel
✔ RUNNING → CANCEL_REQUESTED
✔ Agent 执行 SIGTERM
✔ Grace Period
✔ SIGKILL fallback
✔ 最终 CANCELLED
✔ Cancel / Success Race 使用 CAS
```

---

# 62. 核心设计原则

本次改造最终只需要坚持四句话：

```text
① Scheduler 决定“什么时候执行”

② Execution 记录“这一次执行是什么”

③ Lease 决定“谁拥有这次执行”

④ Process Controller 决定“如何结束这次执行”
```

因此：

```text
Scheduler
    ≠
Execution
    ≠
Process
```

这三个边界必须保持。

---

# 63. P0 最终压缩版

```text
P0
────────────────────────────────

① Execution State CAS

   PENDING
      ↓
   RUNNING
      ↓
   SUCCESS / FAILED / TIMEOUT / CANCELLED


② Execution Lease

   RUNNING
      ↓
   heartbeat
      ↓
   lease_until
      ↓
   Worker crash
      ↓
   lease expired
      ↓
   Recovery


③ RunOnce Dedup

   request_id
      ↓
   UNIQUE
      ↓
   one request
      ↓
   one execution


④ Timeout

   RUNNING
      ↓
   timeout_at
      ↓
   SIGTERM
      ↓
   grace period
      ↓
   SIGKILL
      ↓
   TIMEOUT


⑤ Cancel

   RUNNING
      ↓
   CANCEL_REQUESTED
      ↓
   SIGTERM
      ↓
   SIGKILL
      ↓
   CANCELLED
```

---

# 64. 结论

本次 P0 不需要增加复杂的分布式基础设施。

核心只是：

```text
Execution
    ↓
State CAS
    ↓
Lease
    ↓
Heartbeat
    ↓
Crash Recovery
    ↓
RunOnce Dedup
    ↓
Timeout / Cancel
```

完成以后，`schedule-job` 的核心能力从：

> 能够可靠地调度任务

提升到：

> **能够可靠地管理一次任务执行的完整生命周期。**

同时保持现有：

```text
MySQL
+
Swoolefy
+
Cron Agent
+
CronManager
+
Runtime Scheduler
```

架构不变，只在 Runtime 与持久化 Execution 状态之间补齐可靠性协议。

最终目标不是追求复杂的“分布式 Exactly Once”，而是通过：

```text
CAS
+
Lease
+
Dedup
+
Process Lifecycle
```

把最现实、最容易发生的生产故障闭环解决。
