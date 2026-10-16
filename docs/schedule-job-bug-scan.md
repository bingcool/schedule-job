> 分支：`main`，审查日期：2026-10-16。  
> 范围：对 schedule-job 全量源码逐文件核对（执行可靠性链路 / 调度配置 / 命令守卫 / 告警 / RBAC / 鉴权）。  
> 方式：人工控制流 + 跨文件语义审查，并对照 `docs/schedule-job-P0-bug.md`、`docs/schedule-job-P0-Reliability-Technical-Plan.md` 逐条核实（未使用 PHPStan / rector 等自动化工具）。  
> 说明：以下均为**当前代码上仍能站得住脚的问题或风险点**，按严重度与可信度分档（🔴 高 / 🟠 中 / 🟡 低）。越权类问题建议结合对 `EDITOR_TASK_GROUP` 角色的实际权限预期再确认一次。  
> 修复状态：历史两个 P0 **已在当前代码落地**（见「已修复落地项说明」）；**#1 ~ #7 已修复**。  

# schedule-job 代码审计与 Bug 分析

## 已修复落地项说明（历史 P0，当前代码已落地）

> 以下为 `docs/schedule-job-P0-bug.md` 记录的既有问题，经逐文件核对确认**修复已落地**，属既有加固成果，非本次新发现，仅作存档说明。

- `recoverLease()` / `recoverTransition()` 已用 **Lease Snapshot CAS**（`id + status + lease_owner + lease_until + lease_until < recoveryNow`）；K8s 改为「只读 observe → DEFER/CAS → 成功才 DELETE」；CAS 失败不 Kill、不删 Job（见 `App/Module/Cron/Service/ExecutionService.php` `recoverLease()` / `recoverTransition()`、`App/Module/Cron/Repository/CronTaskLogRepository.php` `casRecoverLease()`）。
- RunOnce 已建 `UNIQUE KEY uk_request_id`，并有 `run_once_claim` 门闩 + `insertExecution()` 识别 1062 抛 `CronRunOnceAlreadyClaimedException`；SKIPPED / REGISTER / UNREGISTER 均剥离 `request_id`。
- RuntimeGuard 顺序已调整为 Heartbeat → Recovery → Timeout/Cancel。

在此基础上发现以下**新的真实 bug**，按严重度排列。

---

## 🔴 高危：越权（RBAC 读写不一致）

### 1. EDITOR 角色可跨节点分组增删改任意任务

`App/Module/Staff/Service/StaffUserService.php` → `canManageCronTask()`：

```php
if ($this->isEditorTaskGroupUser($userId)) {
    return true;   // ← 无条件放行，未校验任务所属分组
}
```

`App/Module/Cron/Service/CronTaskManagerService.php` → `assertTaskManageAllowed()` 只调用它，且
`updateTask / deleteTask / switchTaskStatus / enqueueRunOnce / batchSwitchStatus` 都是
`findById($id)` → `assertTaskManageAllowed($task)`，**不调用 `assertTaskVisible($id)`**。

矛盾点：

- 列表/查看走 `viewerAuthorizedNodeGroupIds()`，EDITOR 只能**看到自己分组**的任务（`scopedTaskIds()`）；
- 但**写路径**对 EDITOR 直接返回 true，不看分组。

后果：一个 `EDITOR_TASK_GROUP` 用户只要知道（或枚举）任意 `task id`，就能编辑/删除/启停/立即执行
**其他团队节点**上的任务，包括 Shell 任务 —— 典型水平越权（读受限、写不受限）。
对照 `transferTaskOwner()` 反而正确校验了 `userHasNodeGroup()`，说明「按分组约束编辑者」本就是设计意图。

建议：`canManageCronTask`（或 `assertTaskManageAllowed`）对 EDITOR 增加
「任务 `node_id` 所属 group ∈ 该用户授权分组」判断。

### 2. 创建/更新任务不校验 node_id 是否存在、是否属于操作者分组

`App/Module/Cron/Service/CronTaskPayloadBuilder.php` → `build()` 只校验 `$nodeId > 0`；
`CronTaskManagerService` 的 `createTask()` / `updateTask()` 都没查 `cron_agent_node` 是否存在、
是否软删、是否在操作者授权分组内。

后果：

- 可把任务绑到**不存在/已删除**的节点 → 任务永远不被任何 Agent 拉取执行，且静默无提示；
- 配合 bug#1，可把任务绑到**别人分组**的节点上执行。

---

## 🟠 中危：配置与运行时健壮性

### 3. K8s Worker 默认并发/生命周期与其自述的安全约束矛盾

`App/WorkerCron/conf/schedule_k8s_conf.php` 类注释明确论证「`limit_run_coroutine_num` ≈ 50、
`life_time` 应缩短到 1h」，但代码默认值是：

```php
'life_time'  => (int) env('CRON_K8S_WORKER_LIFE_TIME', 86400),          // 24h，非 1h
'limit_run_coroutine_num' => (int) env('CRON_K8S_MAX_CONCURRENCY', 1000), // 1000，非 50
```

`limit_run_coroutine_num=1000` 实际等于取消了 K8s 长任务本该有的背压（K8s 任务是分钟级长占用，
本意用它限流）。而 `App/.env` 与 `App/.env.example` 的示例值（50/3600 vs 1000/86400）也彼此矛盾。

建议：代码默认值改成注释所述的 50 / 3600，或反过来修订注释，别让「设计意图」和「实际默认」并存。

### 4. `agentReport` 在 `transition()` 失败时整条上报被静默丢弃

`App/Module/Cron/Service/CronTaskManagerService.php` → `agentReport()`：当带 `status` 时只调用
`$executionService->transition($id, $from, $to, $row)`。若此时该行已被 Recovery / 看护抢先置为终态
（`$from` 已是终态 → `isLegalTransition` 返回 false，或 CAS 命中 0 行），`transition()` 返回 false，
此路径**没有** `writeRuntime` 里那种 `persistMessageIfChanged` 兜底，导致远程 Agent 上报的
`message / duration / exit_code / http_status` 全部丢失。

建议：对失败分支补一个「仅追加 message/pid、不动 status」的兜底写入。

---

## 🟡 低危 / 一致性问题

### 5. `updateExecution` 未落实「取消优先于超时」

`ExecutionService::finishOwned()` 与 K8s Hook 都把 `CANCEL_REQUESTED + TIMEOUT` 归一为 `CANCELLED`，
但 `updateExecution()` 里 `from=CANCEL_REQUESTED`、执行器回报 `TIMEOUT` 时会直接落到 TIMEOUT
（强制 `failure_reason=TIMEOUT`），语义与其余两处不一致。

### 6. `recoverLease` 会把 K8s 观测到的真实 SUCCESS 覆盖为 CANCELLED

`ExecutionService::recoverLease()`：`observe()` 若发现 Job 实际已 `COMPLETE`（`$to=SUCCESS`），
但只要 `from=CANCEL_REQUESTED` 就无条件改写为 `CANCELLED`，丢失「其实跑成功了」的结论。
需确认是否符合预期。

### 7. `resolveNodeIdsForGroups` 死代码

`CronTaskManagerService::resolveNodeIdsForGroups()` 里 `$nodeIds = [];` 赋值后立即被 `return` 覆盖，
是无用语句（无害，建议清理）。

---

## 已核查、未发现问题的点

- Lease / RunOnce 唯一约束与状态机语义；
- `ExecutionLeaseConfig` 的 `EXECUTION_LEASE_DURATION < 20` 启动守卫；
- `ResetPasswordTokenService` 的 HS256 定长令牌（跨用户 `userId` 校验、与 session 密钥隔离）；
- `ShellCommandGuard` 黑名单（定位为 best-effort，非沙箱）；
- `StaffMenuPermissionService` 对未映射路由默认拒绝（fail-safe）；
- Agent 接口（`/agent/tasks`、`/agent/heartbeat`、`/agent/report`）只挂 CORS 不挂 JWT；
- `/health`、`/ready` 保持公开。

---

## 修复优先级建议

1. **先修 #1、#2（越权）** —— 影响数据隔离与命令执行安全。
2. 其次 **#3、#4** —— 影响 K8s 稳定性与远程上报可靠性。
3. #5 ~ #7 属一致性/整洁性，可随相关改动一并处理。

---

## 本次审查项状态一览

> 本次扫描仅做静态分析，未改动任何业务代码；下表条目均为**待修复**，保留备查。

| 编号 | 严重度 | 位置 | 结论 | 状态 |
|------|--------|------|------|------|
| #1 | 🔴 高 | `StaffUserService::canManageCronTask` | EDITOR 可跨分组增删改任意任务 | 已修复：`assertTaskManageAllowed` 先按授权分组做 `assertTaskVisible` |
| #2 | 🔴 高 | `CronTaskPayloadBuilder::build` / `createTask` / `updateTask` | 不校验 node_id 存在与分组归属 | 已修复：`assertNodeAssignable` 校验节点存在且属于操作者分组 |
| #3 | 🟠 中 | `App/WorkerCron/conf/schedule_k8s_conf.php` | 默认并发/生命周期与注释矛盾 | 已修复：未设置环境变量时默认 `life_time=3600`、`limit_run_coroutine_num=50` |
| #4 | 🟠 中 | `CronTaskManagerService::agentReport` | `transition()` 失败时上报被丢弃 | 已修复：`persistUnappliedReport` 补写 message/pid/耗时/退出码/HTTP 状态，不改 status |
| #5 | 🟡 低 | `ExecutionService::updateExecution` | 取消优先于超时未落实 | 已修复：`CANCEL_REQUESTED` 收到 `TIMEOUT` 时记为 `CANCELLED` |
| #6 | 🟡 低 | `ExecutionService::recoverLease` | 观测到的 SUCCESS 被改写为 CANCELLED | 已修复：Job 已成功时保留 `SUCCESS`，其余取消仍记 `CANCELLED` |
| #7 | 🟡 低 | `CronTaskManagerService::resolveNodeIdsForGroups` | 死赋值 | 已清理 |

### 附：历史 P0 落地确认

| 项目 | 位置 | 状态 |
|------|------|------|
| Lease Recovery CAS | `ExecutionService::recoverLease` / `casRecoverLease` | 已落地 |
| RunOnce 去重（UNIQUE + 门闩 + 1062） | `cron.sql uk_request_id` / `claimRunOnce` / `insertExecution` | 已落地 |
| RuntimeGuard 顺序（Heartbeat→Recovery→Timeout/Cancel） | `ExecutionRuntimeGuard::guard` | 已落地 |
