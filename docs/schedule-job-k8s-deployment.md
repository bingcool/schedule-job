# schedule-job Kubernetes：用 Deployment Template 派生一次性 Job

> 状态：**P0 已实现**（swoolefy 框架 + schedule-job 应用 + Admin UI + RBAC 清单）。  
> 落地清单见 §18，环境变量与部署步骤见 §22。P1（§19）仍未开始。

---

## 0. 对照现网：哪些合理、哪些必须改

### 0.1 保留（方向正确）

- 不保存 image；权威来源是执行当下的 `Deployment.spec.template`。
- 不使用 Kubernetes CronJob，避免两套 Scheduler。
- 不使用 `kubectl exec` / `proc_open("kubectl ...")`，只走 Kubernetes HTTP API。
- 只覆盖 `command` / `args`，并做成 argv 数组，不经过 Shell。
- Job 确定性命名 + `409 AlreadyExists` 防重复创建。
- `backoffLimit: 0`，Kubernetes 不负责业务重试。
- 只保留目标业务 Container，默认不带 Service Sidecar。
- `cron_scheduled_task_record` 继续做 Slot 去重，Job 做 Execution 的容器运行。

### 0.2 原稿与现网冲突（已修正）

| 原稿 | 现网事实 | 修正 |
|---|---|---|
| `ExecutionService → KubernetesTaskExecutor` | `ExecutionService` 只写 `cron_task_log` / Lease / Cancel，**从不执行** Shell/HTTP | Executor 挂在 **Agent** `CronExecutorInterface`，与 `ShellExecutor` / `HttpExecutor` 同级 |
| 所有 schedule-job 副本都抢同一 Slot | 任务按 `node_id = CRON_NODE_ID` 拉取；Admin（`cli.php :9502`）不执行 | 只有绑定该节点的 Agent（`cron.php :9506`）会拉到任务并 Create Job |
| Retry = 新的 Execution + 新 Job | `CronManager::runWithRetry` 复用同一 `exec_batch_id` / 同一 `cron_task_log` 行 | K8s 也走管线 Retry；Job 名必须带 **attempt**，否则 Failed Job 对象无法复用 |
| Job 名 `schedule-job-{execution_id}` | **Executor 拿不到 `cron_task_log.id`**：`ExecutionSnapshot` 只有 jobId / execBatchId / definition / plannedAt | `sj-{execBatchId}-a{attempt}`，并需给 Snapshot 加 attempt（§9） |
| Cancel 时删 Job | `ExecutionRuntimeGuard` 在 `pid<=0` 时直接 `finishOwned(CANCELLED)` | 会留下**孤儿 Job**，必须先删 Job 再收尾（§11.1） |
| Executor 同步等到 Job 结束 | Worker 有 `life_time` tick reboot、`limit_run_coroutine_num` 上限 | 长跑任务需显式约束（§11.2） |
| Job/Pod 继承 `app: order-service` | Service selector 会把流量打到 Cron Pod | **禁止**复制 Deployment `spec.selector` 匹配的 labels |
| 原样 Deep Copy template | 健康检查、Istio 注入、hostPort 会让 Job 永远不结束或杀错进程 | 必须剥离 probe / lifecycle / sidecar 注入注解 / hostPort |
| 把 k8s 配置塞进现有 `command` | `cron_task.command` 是 `varchar(256)`，且 Shell 与 HTTP 已占用 | 独立 JSON 列 `k8s_spec`，`exec_type=3` |
| 日志无限写入 MySQL | `cron_task_log.message` 是 text，无独立 stdout/stderr 列 | 摘要写入 `message`；完整日志留在 Kubernetes |

---

## 1. 方案目标

schedule-job 继续负责 **何时执行**；Kubernetes 负责 **这次 Execution 的一次性 Pod**。

```text
Go 发布系统
    ↓ 更新 Deployment
Deployment.spec.template   ← 当前期望运行的镜像与环境
    ↓
Agent CronManager 到点（绑定了该任务的 CRON_NODE_ID）
    ↓
Slot 抢占 → writeLog(RUNNING) 得到 cron_task_log.id
    ↓
GET Deployment（执行当下，禁止用 Preview 缓存）
    ↓
Deep Copy spec.template → 消毒 → 只留目标 Container
    ↓
覆盖 command / args → Create Job
    ↓
独立 Pod 跑 /app/bin/task
```

约束：

- Cron 配置一次，之后发布只改 Deployment。
- Cron Pod 与 Service Pod 生命周期独立。
- 不使用 Kubernetes CronJob、`kubectl exec`、Operator / CRD。

---

## 2. 必须对齐的现网架构

```text
Admin  php cli.php start App     HTTP :9502
        CRUD / UI / 入队 runOnce / Cancel API
        不 Create Job，不跑业务命令

Agent  php cron.php start App    Worker :9506
        按 CRON_NODE_ID + exec_type 拉任务
        CronManager：Window → Guard → Slot → RUNNING → Executor
        Shell / HTTP /（本方案）Kubernetes 都在这里跑
```

现网执行类型：

| exec_type | 常量 | Worker | Executor |
|---|---|---|---|
| 1 | `CronProcess::EXEC_FORK_TYPE` | `schedule-fork-task-cron` | `ShellExecutor` |
| 2 | `CronProcess::EXEC_URL_TYPE` | `schedule-url-task-cron` | `HttpExecutor` |
| **3** | `CronProcess::EXEC_K8S_TYPE`（新增） | `schedule-k8s-task-cron`（新增，`worker_num=1`） | `KubernetesExecutor` |

`KubernetesExecutor` 实现 `Swoolefy\Worker\Cron\CronExecutorInterface::run(ExecutionSnapshot): ExecutionResult`。  
禁止从 Admin / `ExecutionService` 调 Kubernetes API。

`ExecutionService` 只负责：

- RUNNING 落库、`bindScheduleSlot`
- Lease / heartbeat / timeout_at
- Cancel CAS（`CANCEL_REQUESTED`）
- 把 Job/Pod 元数据写入 `cron_task_log`（新列或 `task_item` JSON）

---

## 3. 节点模型（原稿第 39 节的关键错误）

任务始终有 `cron_task.node_id`。Agent 查询是：

```text
WHERE node_id = CRON_NODE_ID AND exec_type = ?
```

因此：

- **不同 `CRON_NODE_ID` 的 Agent 看不到同一条 K8s 任务**，不会一起 Create Job。
- Slot 表 `UNIQUE(cron_id, scheduled_at)` 只挡住 **同一 node 上双 Scheduler**（重复 `CRON_NODE_ID`、`worker_num>1`、reboot 重叠）。
- K8s 任务应绑定到 **具备集群凭证的那台 Agent**（集群内跑 Agent 用 ServiceAccount；集群外用 kubeconfig）。
- Admin 多副本不影响执行，Admin 本来就不跑 Executor。

生产建议：单独一个「K8s Agent」节点，所有 `exec_type=3` 任务绑这个 `node_id`。

---

## 4. 为什么用 Deployment Template

只存 image 会自己维护 env / volumes / SA / affinity，必然和线上 Service 漂移。

`Deployment.spec.template` 是「当前生产期望环境」的权威副本。Cron 在 **Create Job 前即时 GET**，Go 发布系统不必改 schedule-job。

本方案 **不等待 rollout 完成**：template 已是 v1.8.6、旧 Pod 还在时，Cron 用 v1.8.6。这是有意为之。

---

## 5. Cron 配置（exec_type = 3）

不要把 JSON 塞进 `cron_task.command`（`varchar(256)`，且 1/2 已占用语义）。

### 5.1 表结构（P0 迁移）

```sql
ALTER TABLE cron_task
  MODIFY exec_type tinyint(2) NOT NULL DEFAULT 1
    COMMENT '1-shell 2-http 3-kubernetes';

ALTER TABLE cron_task
  ADD COLUMN k8s_spec json DEFAULT NULL
    COMMENT 'exec_type=3：namespace/deployment/container/command/args';
```

`command` 对 type 3 可存展示摘要（如 `order-service /app/bin/task`），**不是**执行权威来源。

`k8s_spec`：

```json
{
  "namespace": "production",
  "deployment": "order-service",
  "container": "order-service",
  "command": ["/app/bin/task"],
  "args": ["reconcile"]
}
```

| 字段 | 说明 |
|---|---|
| namespace | 目标 Namespace，必须在 Agent 允许列表内 |
| deployment | Deployment 名 |
| container | `spec.template.spec.containers[].name`，多容器时必填 |
| command | argv，覆盖目标容器 command；空则保留 template/镜像 ENTRYPOINT |
| args | argv，覆盖目标容器 args；一旦设置（含 `[]`）即整体替换，不追加 |

image / tag / env / volumes：**不保存**。

### 5.2 Payload 校验（对齐 `CronTaskPayloadBuilder`）

- `exec_type` 允许 1 / 2 / 3。
- type 3：`k8s_spec.namespace`、`deployment`、`container` 必填；`command`/`args` 必须是 string 数组。
- `command`/`args` 禁止拼成 `sh -c "..."`。
- `ShellCommandGuard` 不用于 type 3（那是本机 Shell 黑名单）。
- Agent 环境变量：`K8S_ALLOWED_NAMESPACES=production,staging`。配置的 namespace 不在列表内 → 创建任务失败 / 执行 FAILED。

---

## 6. 执行管线（必须跟 CronManager 一致）

调度触发 `source = TRIGGER`：

```text
arm 下一轮 Timer
  → TimeWindow（cron_between / cron_skip）
  → ExecutionGuard（with_block_lapping）
  → ExecutionSnapshot（新 exec_batch_id）
  → claimScheduleSlot（cron_scheduled_task_record）
        DUPLICATE / FAILED → 不写 log、不 Create Job
  → writeLog(RUNNING) → cron_task_log 行落库（Guard 开始心跳续租）
  → bindScheduleSlot(execution_id)
  → KubernetesExecutor.run(snapshot)
        内部：GET Deployment → Build Job → 落 Job 名 → Create → 等待终态
        Lease 由 Guard tick 自动续（见 §11）
  → writeExecutionResult SUCCESS|FAILED|TIMEOUT|CANCELLED
```

Executor 只拿到 `ExecutionSnapshot`；需要 `execution_id` 时用 `(cron_id, exec_batch_id)` 查（`ExecutionService::findByBatch`，有 `idx_cron_exec_batch`）。Job 名不依赖它（§9.1）。

`runOnceNow`：**不抢 Slot**（与现网一致）。会 Create 独立 Job，Job 名用该轮自己的 `exec_batch_id`，天然不冲突。

---

## 7. Template 派生与消毒

```text
GET /apis/apps/v1/namespaces/{ns}/deployments/{name}
        ↓
Deep Copy spec.template     （禁止改 Deployment 原对象）
        ↓
只保留 containers[name=config.container]
        ↓
消毒（§7.1，P0 必须做）
        ↓
覆盖 command / args
        ↓
写入 Job metadata / Pod labels
        ↓
restartPolicy=Never
        ↓
Create Job
```

继承（消毒后仍保留）：

```text
image, imagePullPolicy, env, envFrom, resources,
volumeMounts（仅目标容器上的）, volumes（仍被引用的）,
serviceAccountName, securityContext, imagePullSecrets,
nodeSelector, affinity, tolerations, priorityClassName,
runtimeClassName, dnsPolicy, automountServiceAccountToken
```

Init Container：P0 可继承；若某 initContainer `restartPolicy=Always`（原生 Sidecar）则丢弃。Sidecar 主容器一律不复制。

### 7.1 P0 必须剥离，否则 Job 会挂死或误杀

| 剥离项 | 原因 |
|---|---|
| liveness / readiness / startupProbe | 任务进程不是 HTTP Server，探针失败会杀容器 |
| lifecycle（postStart / preStop） | 仍按 Service 语义，可能拉起多余进程或卡死退出 |
| `hostPort` / 建议去掉 `hostNetwork` | 与 Service Pod 抢宿主机端口 |
| Istio/Linkerd 等注入注解 | Sidecar 不退出 → Job 永不 Complete |
| Deployment `spec.selector` 匹配的 labels | **Service 会把流量打到 Cron Pod** |
| `restartPolicy: Always` | Job Pod 非法，必须改成 `Never` |

Pod / Job labels **只允许**：

```yaml
app.kubernetes.io/managed-by: schedule-job
app.kubernetes.io/name: schedule-job-cron
schedule-job.cron-id: "100"
schedule-job.exec-batch-id: "3f9a1c8e2b7d0456"   # Executor 手上一定有
schedule-job.attempt: "1"
schedule-job.execution-id: "90001"               # 可选，需查库才知道
```

禁止把 `app: order-service` 原样拷到 Job Pod。

容器找不到 → `Execution FAILED`，原因 `KUBERNETES_CONTAINER_NOT_FOUND`，不建 Job。  
Deployment 404 → `KUBERNETES_DEPLOYMENT_NOT_FOUND`。

---

## 8. Job 规格

```yaml
apiVersion: batch/v1
kind: Job
metadata:
  name: sj-3f9a1c8e2b7d0456-a1     # 见 §9
  namespace: production
  labels:
    app.kubernetes.io/managed-by: schedule-job
    schedule-job.cron-id: "100"
    schedule-job.exec-batch-id: "3f9a1c8e2b7d0456"
    schedule-job.attempt: "1"
spec:
  backoffLimit: 0
  parallelism: 1
  completions: 1
  ttlSecondsAfterFinished: 3600
  activeDeadlineSeconds: 3700   # cron_task.timeout + 100；timeout=0 则不设
  template:
    metadata:
      labels:
        app.kubernetes.io/managed-by: schedule-job
        schedule-job.cron-id: "100"
        schedule-job.exec-batch-id: "3f9a1c8e2b7d0456"
        schedule-job.attempt: "1"
    spec:
      restartPolicy: Never
      # 其余来自消毒后的 Deployment template
      containers:
        - name: order-service
          image: registry.xxx.com/order-service:v1.8.7   # 执行当下 template
          command: ["/app/bin/task"]
          args: ["reconcile"]
```

`activeDeadlineSeconds` 必须 **大于** `cron_task.timeout`，避免 K8s 先于业务 Timeout 杀 Pod。`timeout=0` 表示现网不限制，此时不设 deadline，或设一个运维级上限（P1 可配置）。

删除 Job 使用 `propagationPolicy=Background`，连带删 Pod。

---

## 9. Job 名与 Retry

### 9.1 Executor 能拿到什么（决定了命名方案）

`CronExecutorInterface::run()` 的唯一入参是 `ExecutionSnapshot`：

```php
public readonly string $jobId;
public readonly string $execBatchId;   // bin2hex(random_bytes(8))，16 位小写 hex
public readonly TaskDefinition $definition;
public readonly int $plannedAt;
```

**没有 `cron_task_log.id`**，也**没有 attempt**——`runWithRetry` 每轮都传同一个 `$snapshot`。

所以 Job 名不能用 `execution_id`。`execBatchId` 是 Executor 手上唯一全局唯一、且天然符合 DNS-1123 的键：

```text
sj-{execBatchId}-a{attempt}
例：sj-3f9a1c8e2b7d0456-a1        （3 + 16 + 3 = 22 字符，上限 63）
attempt 从 1 起，与 runWithRetry 的 for 循环一致；retry≥1 时最多 a1 / a2（只再试一次）
```

反查：`cron_task_log` 有 `exec_batch_id` 列和 `idx_cron_exec_batch` 索引，Recovery 可由 Execution 行反推 Job 名。

### 9.2 attempt 需要一处框架改动

Kubernetes Job 名不可复用：第一次 Failed 后对象还在（TTL 之前），同名 Create 只会 409 到**已经失败的 Job**，Executor 会把上一次的失败当成本次结果。

现网 `cron_task.retry` = 失败后再试 N 次，**同一** `exec_batch_id` / **同一** `cron_task_log` 行（`CronManager::runWithRetry`，无 retry_delay）。attempt 只存在于 CronManager 的 for 循环里，没传给 Executor。

三种做法，推荐第一种：

| 方案 | 评价 |
|---|---|
| **给 `ExecutionSnapshot` 加 `readonly int $attempt`（默认 1）+ `withAttempt()`，`runWithRetry` 传 `$snapshot->withAttempt($attempt)`** | 推荐。改动小、不破坏冻结语义（execBatchId/definition 不变），性质同当初加 `scheduleSlotClaim` |
| Executor 内存里按 execBatchId 自己计数 | Worker 重启后计数归零，会撞到旧 Job 名 |
| 撞名先 Delete 再 Create | 有竞态，且可能删掉正在跑的 Job |

`backoffLimit: 0`：单次 Job 失败立即结束，由 schedule-job 再 Create `a{n+1}`。

**不要**「FAILED → 新 Execution → 新 Job」——那会改变 Dashboard `COUNT DISTINCT exec_batch_id` 和现网 retry 语义。

Create 409：

```text
AlreadyExists → GET Job
  若 labels.exec-batch-id / attempt 匹配 → 继续监控（幂等）
  否则 → FAILED（名字冲突，属配置/脏数据）
```

---

## 10. Timeout / Cancel / 日志

与现网 Shell 对齐，而不是 HTTP 的 `http_request_time_out`。

| 能力 | 现网 Shell | K8s |
|---|---|---|
| 超时 | `cron_task.timeout` → `timeout_at`；Guard SIGTERM/KILL | Executor 等到 `timeout_at` → DELETE Job → `TIMEOUT` |
| 取消 | Admin `CANCEL_REQUESTED` → Guard 杀 pid | 看到 `CANCEL_REQUESTED` → DELETE Job → `CANCELLED` |
| 无 pid | HTTP 只能等客户端超时 | Job 名 / UID 就是「可杀句柄」 |

stdout/stderr：

- Kubernetes：`GET /api/v1/namespaces/{ns}/pods/{pod}/log?container={container}`
- schedule-job：截断后写入 `cron_task_log.message`（现网没有独立 stdout 列）
- 完整日志留在集群，随 Job TTL 清理

---

## 11. Lease 与崩溃恢复

现网 `ExecutionRuntimeGuard` 按 **pid** 看护 Shell。K8s 没有本机子进程，pid 恒为 0。

**续租不用改**：`ExecutionService::insertExecution` 在 RUNNING 时会 `ExecutionRuntimeGuard::watch($id, $pid, $timeout_at)`，Guard 的 5s tick 是独立 `Swoole\Timer`，只要 `lease_owner` 匹配就自动 `heartbeat`，与 Executor 协程无关。pid=0 不影响心跳。

### 11.1 pid=0 的两个坑（读代码得出，必须处理）

`ExecutionRuntimeGuard::onTick()` 的判断是：

```php
$stopping = $status === CANCEL_REQUESTED || ... || $timedOut || ...;
if ($stopping && ($pid > 0 || $status === ExecutionStatus::CANCEL_REQUESTED)) {
    self::terminate(...);
}
```

**坑 1：超时不会被 Guard 接管。**  
`timeout_at` 到点时 `$timedOut=true`，但 pid=0 且 status 仍是 RUNNING → 条件不成立 → 不进 `terminate()`。也就是说 **Guard 不会替 K8s 结束超时 Execution**。  
→ `KubernetesExecutor` 必须自己盯 `timeout_at`：到点 DELETE Job，返回 `ExecutionResult::timeout()`。

**坑 2：取消会产生孤儿 Job。**  
status 变 `CANCEL_REQUESTED` 时条件成立 → `terminate()` → `pid <= 0` 分支直接：

```php
$service->finishOwned($logId, $owner, CANCELLED, ..., '收到取消请求（无 PID 可杀）');
self::unwatch($logId);
```

Execution 立刻变 CANCELLED，**但 Kubernetes Job 还在跑**，继续占资源、继续写业务数据。HTTP 今天也走这条路（请求终会自己结束，影响小），K8s 不能照搬。

两种改法，选一：

- **A（推荐）**：Guard 增加「删 Job 钩子」——`watch()` 时可注册 `terminator` 回调；`pid<=0` 且有 terminator 时先执行回调（DELETE Job）再 `finishOwned`。对 HTTP 无影响（不注册即维持现状）。
- **B**：Guard 对 `exec_type=3` 完全不接管，由 Executor 轮询 `status`/`timeout_at` 自行收尾。需要 Guard 能识别 type 3 并跳过，否则 A/B 会互相打架。

无论 A 还是 B，**都不要保留现状**：现状 = 取消即孤儿 Job。

### 11.2 长跑 Job 与 Worker 生命周期

现网 Executor 是「一个协程从头等到尾」的同步模型（`ShellExecutor` 轮询 `proc_get_status`，`HttpExecutor` 等 Guzzle）。K8s Job 可能跑几十分钟到几小时，把它塞进同一模型有三处约束：

| 约束 | 现状 | 影响 |
|---|---|---|
| `life_time = 3600*24` | `AbstractBaseWorker::registerTickReboot()` 只对 `CronLocalProcess` 豁免，fork/url Worker 会按 `life_time` tick reboot | reboot 走 `runtimeCoroutineWait()`，会**一直等**在跑的协程。一个 6 小时的 Job 会把 reboot 拖 6 小时 |
| `limit_run_coroutine_num`（fork 200 / url 100） | 长跑 Job 期间协程不释放 | 并发 K8s 任务多时会触顶 |
| `with_block_lapping` | Guard 全程持有 | 同一任务不会重叠——这个行为是对的，保留 |

P0 的现实取舍：

- 建议 K8s 任务按 **分钟级** 设计，并强制配置 `cron_task.timeout`（K8s Worker 上不允许 `timeout=0`，给一个上限如 3600s）。
- Executor 的等待循环必须有硬上限（`timeout` 或全局 `K8S_MAX_WAIT_SECONDS`），到点 DELETE Job 并返回 TIMEOUT，不允许无限等。
- 新 Worker `schedule-k8s-task-cron` 单独配 `life_time`（建议 ≥ 最大 timeout 的数倍）和 `limit_run_coroutine_num`，不要直接抄 fork 的配置。

P1 可以改成「提交 + 收割」两段式：Executor 只 Create Job 就返回，另一个 reconcile Timer 扫 RUNNING 的 Execution 去 GET Job 并收尾。那样协程不被长期占用，但要重构 `CronExecutorInterface` 的同步返回语义，不适合第一版。

### 11.3 Crash Recovery

`RUNNING` 且 Lease 过期时，**不要**在异节点用 Shell 语义重跑。应：

- 读 `k8s_job_name` → GET Job
- Job 已 Complete/Failed → 回写对应终态
- Job 仍 Active → **不删 Job、不改执行记录**，等下一轮扫到终态再落库。Admin 已取消的除外（仍 DELETE）。不补 Create
- 没记到 Job 名 → 用 `exec_batch_id` 反推 `sj-{execBatchId}-a*`，再用 label 列 Job；集群里也没有则视为 Create 前崩溃 → FAILED，**不补 Create**（Slot 已占，补跑会打乱「该 Slot 只跑一次」；RunOnce 另议）

Worker reboot（`life_time`）与崩溃不同：reboot 会等协程跑完，正常情况下 Job 能自然收尾。真正需要 re-attach 的是 kill -9 / OOM / 机器宕机。

Slot 已占但 RUNNING 行还没插入就崩溃：该 Slot 丢失。这是现网 Shell/HTTP 已有不变量，K8s 第一版不单独修复。

---

## 12. Execution 元数据

P0 写入 `cron_task_log.task_item` JSON（避免第一版就改很多列），Recovery 能读到即可：

```text
k8s_namespace
k8s_job_name       # sj-{execBatchId}-a{attempt}
k8s_job_uid
k8s_pod_name
k8s_attempt
k8s_image          # 实际用的 image，便于审计；仍不是配置源
```

Job 名必须在 **Create 之前**先落库（`appendLog` 或 `task_item` 预写），否则「Create 成功但紧接着崩溃」会丢失句柄。有 `exec_batch_id` 可反推是兜底，不是主路径。

P1 再拆独立列并加索引。

状态以 **Job.status** 为主：

```text
succeeded >= 1     → SUCCESS
failed >= 1        → FAILED（若已过 timeout_at 则 TIMEOUT）
Complete 条件      → SUCCESS
Failed 条件        → FAILED
```

Pod 用于日志、exitCode、OOMKilled。用 label `schedule-job.exec-batch-id` + `schedule-job.attempt` 找 Pod，不依赖 Pod Name 前缀。

---

## 13. 代码分层（Agent）

```text
CronManager.runExecutionPipeline
        │
        ▼
KubernetesExecutor          implements CronExecutorInterface
        ├── Swoolefy\Support\Kubernetes\ClientInterface
        └── Swoolefy\Support\Kubernetes\JobTemplateBuilder
```

不要：

```text
ExecutionService → KubernetesTaskExecutor   # 错层
Exec::run("kubectl ...")
```

Client 最小方法：

```text
getDeployment(ns, name)
createJob(ns, job)
getJob(ns, name)
deleteJob(ns, name)
listPods(ns, labelSelector)
getPodLogs(ns, pod, container)
```

认证：

- 集群内：ServiceAccount + in-cluster config
- 集群外：`KUBECONFIG` / 显式 API Server + token

Namespace 白名单见 §5.2。

P0 用 GET Job 轮询（Swoole 协程里 sleep，与 HTTP Executor 等待响应同类）。Watch 放到 P1。

---

## 14. Kubernetes API 错误

| HTTP | 行为 |
|---|---|
| 400 | Template 非法 → 本 attempt FAILED |
| 401 / 403 | 权限问题 → FAILED，记 `KUBERNETES_FORBIDDEN` |
| 404 Deployment | 不建 Job → FAILED |
| 404 Job（监控中） | 若 TTL 已清且曾 Complete → 保持已有终态；否则 FAILED |
| 409 Create | GET 后继续监控（§9） |
| 429 / 5xx | 可按现网 Executor 失败返回；管线 `retry` 会再 attempt。不要在 Client 里再套一套复杂重试把 Job 建重 |

---

## 15. RBAC（跑在集群内的 K8s Agent）

```yaml
rules:
  - apiGroups: ["apps"]
    resources: ["deployments"]
    verbs: ["get"]
  - apiGroups: ["batch"]
    resources: ["jobs"]
    verbs: ["create", "get", "list", "watch", "delete"]
  - apiGroups: [""]
    resources: ["pods"]
    verbs: ["get", "list", "watch"]
  - apiGroups: [""]
    resources: ["pods/log"]
    verbs: ["get"]
```

按 Namespace RoleBinding，不要 cluster-admin，不要 Deployment update。

Secret/ConfigMap：只继承 Template 里的 **引用名**，Cron 配置不存 Secret 值。Agent SA 不需要 get secrets（Pod 用业务 SA 拉）。

---

## 16. Admin UI

执行类型增加：

```text
○ Shell    exec_type=1
○ HTTP     exec_type=2
● Kubernetes  exec_type=3
```

表单：Namespace / Deployment / Container / Command（多行=数组）/ Args（多行=数组）。

只读 Preview：「当前 Deployment 镜像 = …」。Preview 禁止写入任务配置，禁止作为 Create Job 的 image 来源。

---

## 17. 最终配置模型

```json
{
  "exec_type": 3,
  "node_id": 12,
  "k8s_spec": {
    "namespace": "production",
    "deployment": "order-service",
    "container": "order-service",
    "command": ["/app/bin/task"],
    "args": ["reconcile"]
  }
}
```

`node_id=12` 必须是带 Kubernetes 凭证的 Agent。

---

## 18. P0 实施范围（已完成）

| # | 内容 | 落地位置 |
|---|---|---|
| P0-1 | `exec_type=3` + `k8s_spec` JSON + PayloadBuilder / fetchCronTask | `migrations/upgrade_kubernetes_exec_type.sql`、`CronTaskPayloadBuilder`、`CronTaskService::fetchK8sCronTask` |
| P0-2 | Worker `schedule-k8s-task-cron`（`worker_num=1`，独立 life_time / 协程上限） | `App/WorkerCron/conf/schedule_k8s_conf.php`、`ScheduleK8sCronProcess` |
| P0-3 | `KubernetesExecutor` + Kubernetes HTTP Client（禁止 kubectl） | `Swoolefy\Worker\Cron\KubernetesExecutor` / `Swoolefy\Support\Kubernetes\Client` |
| P0-4 | Template Deep Copy + 只留目标容器 + §7.1 消毒 | `Swoolefy\Support\Kubernetes\JobTemplateBuilder` |
| P0-5 | command/args 数组覆盖（三态语义） | `KubernetesJobSpec` + `JobTemplateBuilder::applyArgv` |
| P0-6 | `ExecutionSnapshot::withAttempt()`；Job 名 `sj-{execBatchId}-a{attempt}`；409 幂等 | `ExecutionSnapshot`、`CronManager::runWithRetry`、`KubernetesExecutor::createJobIdempotent` |
| P0-7 | `backoffLimit=0`；Retry 走 CronManager 同批次 | `JobTemplateBuilder::build` |
| P0-8 | Executor 自己盯 timeout_at；Guard 取消路径先 Delete Job 再收尾 | `KubernetesExecutionHook::stopSignal`、`ExecutionRuntimeGuard::attachTerminator` |
| P0-9 | Create 前落 Job 名；`task_item` 写 Job/Pod 元数据；message 写日志摘要 | `KubernetesExecutionHook::onJobPlanned`、`ExecutionService::mergeTaskItemMeta` |
| P0-10 | Namespace 白名单 + 最小 RBAC | `ExecutorOptions::isNamespaceAllowed`、`deploy/kubernetes/schedule-job-agent-rbac.yaml` |
| P0-11 | K8s 任务强制 `timeout>0` + Executor 等待硬上限 | `CronTaskPayloadBuilder`（写入时拒绝）、`ExecutorOptions::resolveWaitSeconds`（执行时兜底） |
| P0-12 | Lease 过期按 Job 终态收尾；仍 Active 则保留 Job，下一轮再收割 | `KubernetesCrashRecovery`、`ExecutionService::recoverRow` |

回归测试：`PHPUintTest/Unit/Worker/Cron/KubernetesJobSpecTest.php`、
`KubernetesJobTemplateBuilderTest.php`、`KubernetesExecutorTest.php`、
`KubernetesJobStatusTest.php`。

---

## 19. P1

```text
Watch Job
独立 k8s_* 列与索引
Init/Sidecar 更细策略
Cron 级 resource / nodeSelector 覆盖
多集群 kubeconfig
完整日志转存对象存储
Job TTL / deadline 可配置
Deployment 注入注解白名单
rollout 完成后再跑（可选开关）
```

---

## 20. 明确不做（第一版）

```text
Kubernetes CronJob
kubectl / Pod Exec
Operator / CRD / Helm / 自定义 Controller
MQ / DAG
schedule-job 自己存 image version
Admin 进程 Create Job
把 K8s 配置写入 cron_task.command
复制 Deployment selector labels
继承 livenessProbe / Istio sidecar
```

---

## 21. 架构原则（修订后）

```text
Go 发布系统     → 更新 Deployment（镜像与环境）
schedule-job    → 何时执行（表达式 / Slot / Retry / Execution）
绑定节点的 Agent → KubernetesExecutor 把 template 变成一次性 Job
Kubernetes Job  → 独立 Pod 跑 /app/bin/task
```

> Cron 配置一次，Deployment 持续发布，到点执行用 **当时** 的 spec.template。  
> Service Pod 与 Cron Pod 镜像环境尽量一致，**标签与探针必须分开**，生命周期完全独立。

---

## 22. 部署

### 22.1 迁移

```bash
mysql < migrations/upgrade_kubernetes_exec_type.sql
kubectl apply -f deploy/kubernetes/schedule-job-agent-rbac.yaml   # 按目标 Namespace 改
```

### 22.2 K8s Agent 环境变量

集群凭证（二选一）：

| 变量 | 说明 |
|---|---|
| —（集群内） | 自动读 `KUBERNETES_SERVICE_HOST/PORT` 与 ServiceAccount token / ca.crt，token 按 60s TTL 重读以兼容轮换 |
| `K8S_API_SERVER` + `K8S_TOKEN` | 集群外。可选 `K8S_CA_CERT_FILE`；`K8S_VERIFY_TLS=0` 只用于本地调试 |

执行策略：

| 变量 | 默认     | 说明 |
|---|--------|---|
| `K8S_ALLOWED_NAMESPACES` | 空（不限制） | **生产必填**，逗号分隔。为空时任何写错的 namespace 都能塞 Pod |
| `K8S_MAX_WAIT_SECONDS` | 3600   | Executor 等待单个 Job 的硬上限，夹住 `cron_task.timeout` |
| `K8S_POLL_INTERVAL` | 3      | 轮询 Job 状态的间隔秒 |
| `K8S_JOB_TTL_SECONDS` | 120    | Job 完成后由 K8s 回收的 TTL |
| `K8S_DEADLINE_PADDING` | 100    | `activeDeadlineSeconds = timeout + 该值`，保证 schedule-job 先判超时 |
| `K8S_LOG_TAIL_LINES` | 50     | 写进 message 的 Pod 日志行数 |
| `K8S_REQUIRE_TIMEOUT` | 1      | 是否拒绝 `timeout=0` 的 K8s 任务 |
| `K8S_API_TIMEOUT` | 15     | 单次 API 调用超时秒 |

Worker 规格：

| 变量 | 默认 | 说明 |
|---|---|---|
| `CRON_K8S_WORKER_LIFE_TIME` | 3600 | 比 fork Worker 短：reboot 会等在跑的协程，长 Job 会把 reboot 拖住 |
| `CRON_K8S_MAX_CONCURRENCY` | 50 | ≈ 本节点允许同时在跑的 Job 数 |

### 22.3 上线顺序
P1
1. 迁移 DB 与 RBAC。
2. 在**具备集群凭证**的机器上配好上表环境变量，启动 Agent：`php cron.php start App`。
   `schedule-k8s-task-cron` 会随另外两个 Worker 一起拉起。
3. Admin 里新建 `exec_type=3` 任务，`node_id` 必须指向第 2 步那台 Agent ——
   Agent 是按 `node_id + exec_type` 拉任务的，绑错节点会「保存成功但永远不执行」。
4. 先用一个 `timeout` 较小的任务点「立即执行」验证，确认 `cron_task_log.task_item`
   里能看到 `k8s_job_name` / `k8s_pod_name`，再放开表达式。
