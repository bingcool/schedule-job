# schedule-job Kubernetes：用 Deployment Template 派生一次性 Job

> 状态：已对照现网代码修订（Admin / Agent / CronManager / Slot / Retry / Lease）。  
> 本文件是设计方案，不是已落地实现。

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
| Job 名 `schedule-job-{execution_id}` | 同一 Execution 可能 retry 多次 | `sj-{logId}-a{attempt}` |
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
  → writeLog(RUNNING) → cron_task_log.id = execution_id
  → bindScheduleSlot(execution_id)
  → KubernetesExecutor.run(snapshot)
        内部：GET Deployment → Build Job → Create → 等待终态
        等待期间必须续租 Execution Lease（见 §11）
  → writeExecutionResult SUCCESS|FAILED|TIMEOUT|CANCELLED
```

`runOnceNow`：**不抢 Slot**（与现网一致）。会 Create 独立 Job，Job 名仍带该次 `execution_id`。

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
schedule-job.execution-id: "90001"
schedule-job.attempt: "0"
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
  name: sj-90001-a0          # 见 §9
  namespace: production
  labels:
    app.kubernetes.io/managed-by: schedule-job
    schedule-job.cron-id: "100"
    schedule-job.execution-id: "90001"
    schedule-job.attempt: "0"
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
        schedule-job.execution-id: "90001"
        schedule-job.attempt: "0"
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

Kubernetes Job 名不可复用：第一次 Failed 后对象还在（TTL 之前），同名 Create 只会 409 到 **已经失败的 Job**。

现网 `cron_task.retry` = 失败后再试 N 次，**同一** `exec_batch_id` / **同一** `cron_task_log` 行（`CronManager::runWithRetry`，无 retry_delay）。

因此 Job 名：

```text
sj-{execution_id}-a{attempt}
```

- `execution_id` = `cron_task_log.id`（RUNNING 插入之后才有，Create Job 必须在这之后）
- `attempt` 从 0 开始；`retry=2` 最多 `a0` `a1` `a2`
- DNS-1123：小写、数字、`-`，最长 63。`sj-` 前缀短于 `schedule-job-`。

`backoffLimit: 0`：单次 Job 失败立即结束，由 schedule-job 再 Create `a{n+1}`。

**不要**「FAILED → 新 Execution → 新 Job」——那会改变 Dashboard `COUNT DISTINCT exec_batch_id` 和现网 retry 语义。

Create 409：

```text
AlreadyExists → GET Job
  若 labels.execution-id / attempt 匹配 → 继续监控（幂等）
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

现网 `ExecutionRuntimeGuard` 按 **pid** 看护 Shell。K8s 没有本机子进程。

P0：

1. `KubernetesExecutor` 在 `run()` 等待 Job 期间，按 `EXECUTION_LEASE_DURATION` 续租（`heartbeat_at` / `lease_until`），否则 Recovery 会把 RUNNING 当成 Worker 崩溃。
2. Guard 对 type 3 不走 SIGKILL；超时/取消改为通知 Executor 删 Job（或 Executor 自己轮询 `status=CANCEL_REQUESTED` / `timeout_at`）。
3. Crash Recovery：`RUNNING` 且 Lease 过期时，**不要**在异节点用 Shell 语义重跑。应：
   - 读 `k8s_job_name` → GET Job
   - Job 已 Complete/Failed → 回写对应终态
   - Job 仍 Active → **本节点** 才能续监控；异节点只标记 `WORKER_CRASH`（现网 Recovery「禁止异节点重跑」）
   - 无 Job 名但有 `execution_id` → 按确定性名字 GET `sj-{id}-a{attempt}`；没有则视为创建前崩溃 → FAILED，不补 Create（Slot 已占，补跑会打乱「该 Slot 只跑一次」；RunOnce 另议）

Slot 已占但 RUNNING 行还没插入就崩溃：该 Slot 丢失。这是现网 Shell/HTTP 已有不变量，K8s 第一版不单独修复。

---

## 12. Execution 元数据

P0 写入 `cron_task_log.task_item` JSON（避免第一版就改很多列），Recovery 能读到即可：

```text
k8s_namespace
k8s_job_name
k8s_job_uid
k8s_pod_name
k8s_attempt
k8s_image          # 实际用的 image，便于审计；仍不是配置源
```

P1 再拆独立列并加索引。

状态以 **Job.status** 为主：

```text
succeeded >= 1     → SUCCESS
failed >= 1        → FAILED（若已过 timeout_at 则 TIMEOUT）
Complete 条件      → SUCCESS
Failed 条件        → FAILED
```

Pod 用于日志、exitCode、OOMKilled。用 label `schedule-job.execution-id` + `schedule-job.attempt` 找 Pod，不依赖 Pod Name 前缀。

---

## 13. 代码分层（Agent）

```text
CronManager.runExecutionPipeline
        │
        ▼
KubernetesExecutor          implements CronExecutorInterface
        ├── KubernetesClientInterface     只做 HTTP API
        └── KubernetesJobTemplateBuilder  Deep Copy + 消毒 + 覆盖 argv
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

## 18. P0 实施范围

```text
P0-1  exec_type=3 + k8s_spec JSON + PayloadBuilder / fetchCronTask
P0-2  Worker schedule-k8s-task-cron（worker_num=1）+ KubernetesExecutor
P0-3  Kubernetes HTTP Client（禁止 kubectl）
P0-4  Template Deep Copy + 只留目标容器 + §7.1 消毒
P0-5  command/args 数组覆盖
P0-6  Job 名 sj-{logId}-a{attempt}；409 幂等
P0-7  backoffLimit=0；Retry 走 CronManager 同批次
P0-8  等待 Job 期间续租 Lease；Timeout/Cancel → Delete Job
P0-9  task_item 写入 Job/Pod 元数据；message 写日志摘要
P0-10 Namespace 白名单 + 最小 RBAC
```

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
