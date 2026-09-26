# Schedule Job

基于 [Swoolefy](https://github.com/bingcool/swoolefy) 的 **Cron 任务调度管理平台 + Agent 执行体**。提供 Web 管理控制台、REST API、多节点 Agent 拉取执行、执行日志与操作审计，适用于 PHP 、Python, Java, Go、Shell等生态下的分布式定时任务场景。

---

## 系统说明

Schedule Job 由两个独立部署、协同工作的子系统组成：**schedule-job-admin-manager（管理端）** 与 **cron agent（节点执行端）**。二者共用同一 MySQL 数据库，通过库表交换任务配置与执行结果。

### schedule-job-admin-manager（管理端）

管理端负责**任务的配置、权限、监控与审计**，不提供脚本执行能力。

| 项目 | 说明 |
|------|------|
| **职责** | Web 控制台、REST API、用户/角色/节点管理、任务 CRUD、执行日志与操作审计 |
| **部署位置** | 通常 1 套（或按需多副本），与业务脚本所在机器无关 |
| **启动命令** | `php cli.php start App` |
| **典型配置** | `App/.env` 中的 MySQL、JWT（`AUTH_JWT_*`）、日志保留天数（`CRON_TASK_LOG_DELETE_DAY`）等 |

在管理台创建/编辑任务后，配置写入 `cron_task` 等表；Agent 通过轮询数据库感知变更，无需管理端主动推送。

### cron agent（节点执行端）

Cron Agent 部署在**实际需要跑定时任务的机器**上，负责本机拉取任务并执行 Shell / HTTP / Kubernetes 调度。

| 项目 | 说明 |
|------|------|
| **职责** | 按 `CRON_NODE_ID` 拉取绑定任务：本机 fork 脚本、发起 HTTP，或向 Kubernetes 提交一次性 Job；上报心跳与执行日志 |
| **部署位置** | 每台执行机各部署 1 套；多台机器 = 多个 Agent 节点 |
| **启动命令** | `php cron.php start App` |
| **必需 `.env` 配置** | `CRON_NODE_ID`、`CRON_NODE_API_KEY`、以及与管理端相同的 **DB 连接**（`DB_HOST_*` 等） |
| **可选配置** | `CRON_POLL_INTERVAL`（拉取间隔）、`CRON_HEARTBEAT_INTERVAL`（节点心跳）、`EXECUTION_LEASE_*`（执行租约，见下表） |

**Shell 任务还需要在 Agent 机器部署目标业务代码。** 管理台里任务的 `command` / `exec_script` 指向本机路径（如 `/home/wwwroot/your-project/script.sh`），Agent 只负责按 Cron 表达式触发执行，**不会**把代码分发到节点；脚本依赖的运行时（PHP、Python、Shell 等）也需在 Agent 机器上预先安装。Kubernetes 任务则跑在集群 Pod 里，Agent 机器本身不必放业务代码，但必须能访问 Kubernetes API。

### 二者如何协作

```
管理端 (cli.php)                    MySQL                         Agent (cron.php)
     │                                │                                │
     │  创建/编辑任务 ──────────────► │ ◄──── 轮询拉取本节点任务 ─────── │
     │  查看日志 / Dashboard ◄─────── │ ────► 写入执行日志 / 心跳 ────── │
     │                                │                                │
     │                                │         本机 fork / HTTP / 提交 K8s Job │
```

1. 在管理端 **Cron Nodes** 创建节点，获得 `node_id` 与 `api_key`（`api_key` 仅展示一次）。
2. 在 Agent 机器配置 `.env`：`CRON_NODE_ID`、`CRON_NODE_API_KEY`、DB 连接。
3. Agent 机器部署 schedule-job 代码及**待执行的 target 项目**。
4. 管理端启动：`php cli.php start App`；Agent 启动：`php cron.php start App`。
5. Agent 根据 `node_id` 拉取任务，在**当前机器**执行；执行记录回写数据库，管理端可查询。

> **注意**：管理端与 Agent **启动入口不同**（`cli.php` vs `cron.php`），默认监听端口也不同（管理端 HTTP `9502`，Agent Worker `9506`）。同一台机器上可以只跑其中一种，也可以同时跑两种（开发环境常见）；生产环境通常分开部署。

---

## 目录

- [系统说明](#系统说明)
- [功能特性](#功能特性)
- [系统架构](#系统架构)
- [技术栈](#技术栈)
- [环境要求](#环境要求)
- [快速开始](#快速开始)
- [配置说明](#配置说明)
- [启动服务](#启动服务)
- [Web 管理台](#web-管理台)
- [界面截图](#界面截图)
- [Agent 节点部署](#agent-节点部署)
- [Kubernetes 任务部署](#kubernetes-任务部署)
- [权限与数据范围](#权限与数据范围)
- [API 概览](#api-概览)
- [数据库表说明](#数据库表说明)
- [目录结构](#目录结构)
- [运维说明](#运维说明)
- [常见问题](#常见问题)
- [License](#license)

---

## 功能特性

### 任务调度

- **Shell / Fork 任务**（`exec_type = 1`）：在 Agent 节点上 fork 子进程执行脚本或命令
- **HTTP 任务**（`exec_type = 2`）：按 Cron 表达式定时发起 HTTP 请求
- **Kubernetes 任务**（`exec_type = 3`）：绑定到具备集群凭证的 Agent，到点后按 Deployment 模板派生一次性 Job（**不使用** Kubernetes CronJob）
- 支持 Cron 表达式、允许/跳过时间段（`cron_between` / `cron_skip`）
- 支持阻塞重叠执行（`with_block_lapping`）、失败重试（`retry`）
- 支持**手动执行一次**（Run Once），跨进程入队后由 Worker 消费

### 多节点 Agent

- 节点注册、分组管理、心跳上报
- 每个节点独立 `node_id` + `api_key` 鉴权
- Worker 定时轮询 DB 拉取本节点任务，Runtime Diff 动态增删改调度
- Admin 与 Agent 可分离部署（Admin 写库，Agent 读库执行）

### 可观测性

- **执行记录**（`cron_task_log`）：批次 ID、状态、耗时、退出码 / HTTP 状态等
- **Dashboard**：任务统计、今日执行趋势、节点在线状态
- **操作审计**（`cron_task_operation_log`）：启用 / 禁用 / 删除 / 执行 / 编辑的前后快照
- 过期执行日志自动硬删除（可配置保留天数）

### 权限管理（RBAC）

- JWT 登录鉴权
- 角色、菜单页面权限
- **节点组授权**：非超管用户仅可见授权节点组下的任务与日志
- 内置角色：`super_admin`（超管）、`editer_task_group`（可维护他人创建的任务）

### 机器人告警

- 全局配置企微 / 钉钉 / 飞书群机器人，节点组绑定其中一个（或不绑定）
- 任务执行 `FAILED` / `TIMEOUT`（非取消）时旁路推送，不改 Execution 状态机
- 发送时读取节点组**当前**绑定；Webhook 明文落库、API 脱敏；写操作仅超级管理员

---

## 系统架构

（组件分工见上文 [系统说明](#系统说明)。）

```mermaid
flowchart TB
    subgraph Admin["schedule-job-admin-manager<br/>cli.php · HTTP :9502"]
        UI["Web 控制台<br/>/cron-admin"]
        API["REST API<br/>/api/v1/*"]
        Tick["PurgeExpiredTaskLogs<br/>每 12h 清理日志"]
        DB[(MySQL)]
        UI --> API
        API --> DB
        Tick --> DB
    end

    subgraph AgentNode["cron agent · 执行机 A<br/>cron.php · Worker :9506"]
        Fork["CronForkProcess<br/>Shell 任务"]
        URL["CronUrlProcess<br/>HTTP 任务"]
        K8s["CronK8sProcess<br/>Kubernetes Job"]
        Script["本机目标项目<br/>script.sh / php …"]
        Cluster["Kubernetes API<br/>一次性 Job"]
        Fork --> Script
        Fork --> DB
        URL --> DB
        K8s --> Cluster
        K8s --> DB
    end

    Admin -.->|"任务配置写入 DB"| DB
    AgentNode -.->|"按 CRON_NODE_ID 拉取 + 写日志/心跳"| DB
```

| 子系统 | 入口 | 启动命令 | 默认端口 | 职责 |
|--------|------|----------|----------|------|
| **schedule-job-admin-manager** | `cli.php` | `php cli.php start App` | `9502` | Web UI、管理 API、JWT 鉴权、日志清理 |
| **cron agent** | `cron.php` | `php cron.php start App` | `9506` | 拉取本节点任务、本机执行脚本/HTTP 或提交 K8s Job、心跳上报 |

> 端口在各自入口文件的 `APP_META_ARR` 中配置，可按环境修改。应用名 `App` 需与 `cli.php` / `cron.php` 中定义一致（区分大小写）。

---

## 技术栈

| 类别 | 选型 |
|------|------|
| 语言 | PHP 8.x |
| 框架 | [bingcool/swoolefy](https://github.com/bingcool/swoolefy) 6.2.x |
| 运行时 | Swoole 扩展 |
| 数据库 | MySQL 5.7+ / 8.x |
| 鉴权 | JWT（HS256） |
| 前端 | Vue 2 + Vue Router（静态 SPA，内置于 `App/Module/Cron/static/cron-admin`） |

---

## 环境要求

- PHP >= 8.4
- 扩展：`swoole`、`pdo_mysql`、`json`、`mbstring`、`openssl`
- Composer 2.x
- MySQL 5.7+ 或 8.x
- Linux / macOS（生产环境建议 Linux）
- swoole 扩展要求swoole >=6.1+。可以直接下载最新的swoole-cli v6.2.2。这样php都不需要安装了。
- 把swoole-cli复制到linux的/usr/local/bin/下，然后创建软链：
```

ln -s /usr/local/bin/swoole-cli /usr/local/bin/php
```

---

## 快速开始

### 1. 克隆与安装依赖

```bash
git clone <your-repo-url> schedule-job
cd schedule-job
composer install
```

| 包 | 分支       |
|---|----------|
| `bingcool/swoolefy` | `~6.3.4` |
| `bingcool/library` | `^6.0.1` |

若安装或升级报错，请一次性指定两个包：

```bash
composer require bingcool/swoolefy:~6.3.4 bingcool/library:^6.0.1
```

### 2. 配置环境变量

```bash
cp App/.env.example App/.env
# 编辑 App/.env，至少配置 MySQL 与 JWT
```

### 3. 初始化数据库

按顺序执行迁移脚本（在目标库中）：

```bash
mysql -h <host> -u <user> -p <database> < migrations/cron.sql

// 或者直接复制cron.sql在面板执行        

```

迁移完成后会预置**超级管理员**账号，用于首次登录：

| 项目 | 值 |
|------|-----|
| 账号 | `admin` |
| 密码 | `123456789` |
| 角色 | `super_admin`（超级管理员） |

> **安全提示**：部署完成后请立即使用上述账号登录，并在「用户管理」中修改密码。生产环境切勿保留默认密码。

系统**已关闭公开注册**，新用户只能由超级管理员或有权限的管理员在后台 **用户管理** 中创建。

### 4. 启动管理端（schedule-job-admin-manager）

```bash
# 前台启动
php cli.php start App

# 守护进程模式
php cli.php start App --daemon=1
```

### 5. 访问管理台

浏览器打开：

```
http://127.0.0.1:9502/cron-admin
```

使用默认超级管理员登录：**账号 `admin`，密码 `123456789`**（见上文数据库初始化）。登录后建议在用户管理中修改密码。

### 6. 创建节点并启动 Cron Agent

1. 在管理台 **Cron Nodes** 中创建节点，记录返回的 `apiKey`（仅展示一次）
2. 在**执行机**配置 `.env`（`CRON_NODE_ID`、`CRON_NODE_API_KEY`、DB），并部署目标业务代码
3. 启动 Agent：

```bash
php cron.php start App
```

---

## 配置说明

主配置文件：`App/.env`（参考 `App/.env.example`）。

### 数据库

| 变量 | 说明 |
|------|------|
| `DB_HOST_NAME` | MySQL 主机 |
| `DB_HOST_PORT` | 端口，默认 `3306` |
| `DB_HOST_DATABASE` | 库名 |
| `DB_USER_NAME` | 用户名 |
| `DB_PASSWORD` | 密码 |

### 鉴权

| 变量 | 说明 |
|------|------|
| `AUTH_JWT_SECRET` | JWT 密钥（**生产必改**） |
| `AUTH_JWT_TTL` | Token 有效期（秒），默认 `7200` |
| `AUTH_JWT_ALGO` | 算法，默认 `HS256` |

### Cron / Agent

| 变量 | 说明 |
|------|------|
| `CRON_NODE_ID` | 当前 Agent 节点 ID（每台机器不同） |
| `CRON_NODE_API_KEY` | 节点 API Key（创建节点时获得） |
| `CRON_POLL_INTERVAL` | Worker 轮询 DB 间隔（秒），默认 `20` |
| `CRON_HEARTBEAT_INTERVAL` | 节点心跳间隔（秒），默认 `15`。只判断 Agent 在不在线，与下面执行租约续期间隔无关 |
| `CRON_DEBUG` | Cron 调试开关 |
| `CRON_TASK_LOG_DELETE_DAY` | 执行日志保留天数，默认 `7`；≤0 表示不自动清理 |

Kubernetes Agent 环境变量见 [Kubernetes 任务部署](#kubernetes-任务部署)。Admin（`cli.php`）不读这些变量。

#### Execution Lease（Agent）

续租间隔不单独配置，由代码计算：`(EXECUTION_LEASE_DURATION / 2) - 5`。

| 配置 | 不设置 | 显式设置 |
|------|--------|----------|
| `EXECUTION_LEASE_DURATION` | 60 秒 | 不能小于 20，否则 Agent 启动抛异常 |
| `EXECUTION_LEASE_RECOVERY_INTERVAL` | 30 秒 | 按设置值 |
| `EXECUTION_TERMINATE_GRACE_PERIOD` | 10 秒 | 按设置值 |

#### 群机器人 Webhook

| 配置 | 不设置 | 说明 |
|------|--------|------|
| `ROBOT_CONNECT_TIMEOUT` | 10 秒 | 连接超时 |
| `ROBOT_REQUEST_TIMEOUT` | 20 秒 | 请求总超时 |
| `WEBHOOK_HOST_WECOM` | `qyapi.weixin.qq.com` | 企微 Webhook 允许主机，逗号分隔 |
| `WEBHOOK_HOST_DINGTALK` | `oapi.dingtalk.com` | 钉钉 Webhook 允许主机，逗号分隔 |
| `WEBHOOK_HOST_FEISHU` | `open.feishu.cn,open.larkoffice.com` | 飞书 Webhook 允许主机，逗号分隔 |

官方对接：企微 [群机器人消息推送](https://developer.work.weixin.qq.com/document/path/91770)；钉钉 [自定义机器人安全设置](https://open.dingtalk.com/document/orgapp/customize-robot-security-settings)（加签 HMAC-SHA256）；飞书 [自定义机器人](https://open.feishu.cn/document/client-docs/bot-v3/add-custom-bot)（签名校验 HMAC-SHA256）。

### 其他

| 变量 | 说明 |
|------|------|
| `ENABLE_LOG_SANITIZE` | 日志脱敏（password/token 等），生产建议开启 |

更多配置见 `App/Config/`（健康检查、限流、文件存储等）。

---

## 启动服务

管理端与 Cron Agent **入口与命令不同**，请勿混用。

### schedule-job-admin-manager（管理端）

```bash
php cli.php start App              # 前台
php cli.php start App --daemon=1   # 守护进程
php cli.php stop App               # 停止
php cli.php restart App            # 重启
php cli.php reload App             # 平滑重载 Worker
php cli.php status App             # 状态
```

### cron agent（节点执行端）

```bash
php cron.php start App
php cron.php stop App
php cron.php restart App
php cron.php status App
```

Agent Worker 进程配置位于：

- `App/WorkerCron/worker_cron_conf.php` — 总入口
- `App/WorkerCron/conf/schedule_fork_conf.php` — Shell 任务
- `App/WorkerCron/conf/schedule_url_conf.php` — HTTP 任务
- `App/WorkerCron/conf/schedule_k8s_conf.php` — Kubernetes 任务（`schedule-k8s-task-cron`，`worker_num=1`）

### 健康检查

HTTP 服务默认暴露（可在 `App/Config/health.php` 调整）：

| 路径 | 用途 |
|------|------|
| `/health` | Liveness |
| `/ready` | Readiness |

---

## Web 管理台

入口：`http://<host>:9502/cron-admin`

| 页面 | 路径 | 说明 |
|------|------|------|
| Dashboard | `#/dashboard` | 任务与执行概览 |
| 计划任务 | `#/tasks` | 任务 CRUD、启停、手动执行 |
| 操作记录 | `#/tasks/operation-logs` | 任务变更审计 |
| 执行记录 | `#/executions` | 按任务 / 状态 / 批次筛选 |
| Cron Nodes | `#/nodes` | 节点与分组管理 |
| 机器人告警 | `#/robots` | 企微 / 钉钉 / 飞书群机器人，节点组绑定后失败/超时告警 |
| Runtime | `#/runtime` | Worker 运行时概览 |
| 用户 / 角色 / 菜单 | `#/users` 等 | RBAC 管理 |

---

## 界面截图

以下为管理台主要页面预览（截图位于 [`docs/images/`](docs/images/)）。

### 计划任务列表

任务列表：筛选、启停、手动执行、创建人过滤等。

![计划任务列表](docs/images/cronlist.png)

### 创建 / 编辑计划任务

配置 Cron 表达式、执行方式（Shell / HTTP / Kubernetes）、节点、重试与阻塞策略等。

![创建计划任务](docs/images/createcron.png)

### 执行记录

按任务、状态、批次查看 Execution，可进入单次执行详情与日志。

![执行记录](docs/images/cronrecord.png)

### 机器人告警

配置企微 / 钉钉 / 飞书 Webhook；在 **Cron Nodes → 节点分组** 绑定后，组内任务失败或超时（非取消）向对应群机器人发告警。

![机器人告警](docs/images/robot.png)

---

## Agent 节点部署

Cron Agent 部署在需要执行定时脚本的机器上。每台机器独立配置 `CRON_NODE_ID`，并需提前部署好**任务所要执行的目标代码项目**（管理台中的命令/脚本路径指向本机实际路径）。

### 部署模型

- **schedule-job-admin-manager**：1 套（或多副本），`php cli.php start App`
- **cron agent**：每台执行机 1 套，`php cron.php start App`，通过 `CRON_NODE_ID` 区分节点

### 配置步骤

1. **在管理端创建节点**  
   `POST /api/v1/nodes` → 响应含 `apiKey`（请立即保存）

2. **在 Agent 机器配置 `.env`**（DB 配置需能连到与管理端相同的库）

```env
# 数据库（与管理端共用）
DB_HOST_NAME=192.168.1.102
DB_HOST_DATABASE=schedule_job
DB_USER_NAME=root
DB_PASSWORD=******
DB_HOST_PORT=3306

# 本 Agent 节点身份（每台机器不同）
CRON_NODE_ID=1
CRON_NODE_API_KEY=<创建节点时返回的 apiKey>

CRON_POLL_INTERVAL=20
CRON_HEARTBEAT_INTERVAL=15

# Execution Lease（均可不设，走默认值）
# EXECUTION_LEASE_DURATION=60
# EXECUTION_LEASE_RECOVERY_INTERVAL=30
# EXECUTION_TERMINATE_GRACE_PERIOD=10

# ROBOT_CONNECT_TIMEOUT=10
# ROBOT_REQUEST_TIMEOUT=20
# WEBHOOK_HOST_WECOM=qyapi.weixin.qq.com
# WEBHOOK_HOST_DINGTALK=oapi.dingtalk.com
# WEBHOOK_HOST_FEISHU=open.feishu.cn,open.larkoffice.com
```

3. **部署目标业务代码**  
   例如任务命令为 `/home/wwwroot/my-app/bin/run.sh`，则 Agent 机器上必须存在该路径及可执行环境。

4. **启动 Cron Agent**

```bash
php cron.php start App          # 前台
php cron.php start App --daemon=1   # 守护进程
```

Kubernetes 任务（`exec_type=3`）必须绑到**具备集群凭证**的 Agent，步骤见下一节。

### 任务拉取方式

Worker 通过 `CronTaskService::fetchCronTask()` 从 DB 拉取本节点、未软删的任务（含禁用任务，由 Runtime Diff 处理启停）。也可通过 HTTP 调试：

```bash
curl 'http://127.0.0.1:9502/api/v1/agent/tasks?nodeId=1&apiKey=YOUR_API_KEY&execType=1'
```

| 参数 | 说明 |
|------|------|
| `nodeId` | 节点 ID |
| `apiKey` | 节点密钥 |
| `execType` | 可选：`1`=Shell，`2`=HTTP，`3`=Kubernetes；省略则返回三类 |

---

## Kubernetes 任务部署

schedule-job 继续负责**何时执行**；Kubernetes 负责这次 Execution 的**一次性 Pod**。不使用 Kubernetes CronJob，也不走 `kubectl`。

```text
Admin（cli.php :9502）     只做 CRUD / UI / 入队 RunOnce / Cancel
        │
        ▼ 写 cron_task（含 node_id、k8s_spec）
      MySQL
        ▲
        │ 按 CRON_NODE_ID + exec_type=3 拉取
Agent（cron.php :9506）    CronManager：时间窗 → 重叠保护 → Slot 抢占 → RUNNING
        │
        ▼ 只有抢到该调度点的那一台 Agent
GET Deployment.spec.template（执行当下，不缓存镜像）
        │
        ▼ 消毒模板（去掉 probe / sidecar 注入 / hostPort / Service selector）
Create Job  sj-{execBatchId}-a{attempt}
        │
        ▼ 协程同步等到 Complete / Failed / 超时 / 取消
写回 cron_task_log
```

和 GLUE / HTTP 一样：任务绑一个 `node_id`，多套 Agent 各自只拉自己的节点。同一调度点靠 `cron_scheduled_task_record` 的 `UNIQUE(cron_id, scheduled_at)` 去重，**只有一个赢家会 Create Job**。Admin 多副本不影响执行。

设计细节见 [`docs/schedule-job-k8s-deployment.md`](docs/schedule-job-k8s-deployment.md)。

### 1. 数据库

新库直接执行 `migrations/cron.sql`（已含 `exec_type=3` 与 `k8s_spec`）。已有库补跑：

```bash
mysql -h <host> -u <user> -p <database> < migrations/upgrade_kubernetes_exec_type.sql
```

### 2. 准备 K8s Agent 节点

生产建议单独建一个「K8s Agent」节点，所有 `exec_type=3` 任务绑这个 `node_id`。该 Agent 必须能访问 Kubernetes API。

**集群内跑 Agent**（推荐）：把 Agent 部署进集群，凭证自动读 `KUBERNETES_SERVICE_HOST/PORT` 与 ServiceAccount。按目标 Namespace 改 `deploy/kubernetes/schedule-job-agent-rbac.yaml` 后应用：

```bash
kubectl apply -f deploy/kubernetes/schedule-job-agent-rbac.yaml
```

权限是按 Namespace 的 Role，不是 ClusterRole：

| 资源 | 动词 | 用途 |
|------|------|------|
| `deployments` | `get` | 读取 `spec.template`，**不改** Deployment |
| `jobs` | `create/get/list/watch/delete` | 提交、等待、超时/取消时删除 |
| `pods` / `pods/log` | `get/list/watch` | 定位 Pod、写日志摘要 |

目标 Namespace 有几个就复制几份 Role，并与 `K8S_ALLOWED_NAMESPACES` 对齐。不给 `secrets`、不给 Deployment 的 update/patch。

**集群外跑 Agent**：在 `.env` 配 API Server 与 token。

```env
# 集群外必填。Docker Desktop 只监听 127.0.0.1:6443，不要填局域网 IP
K8S_API_SERVER=https://127.0.0.1:6443
K8S_TOKEN=<kubectl create token schedule-job-agent -n <ns> --duration=8760h>
# K8S_CA_CERT_FILE=/path/to/ca.crt
K8S_VERIFY_TLS=0
K8S_ALLOWED_NAMESPACES=production
```

`K8S_VERIFY_TLS=0` 只用于本机调试。生产必须校验证书，并填写 `K8S_ALLOWED_NAMESPACES`（为空时写错的 namespace 也能建 Pod）。

### 3. Agent 环境变量

Admin 不读这些变量。集群内跑 Agent 时，凭证相关项可全部留空。

| 变量 | 默认 | 说明 |
|------|------|------|
| `K8S_API_SERVER` + `K8S_TOKEN` | — | 集群外访问 API Server |
| `K8S_CA_CERT_FILE` | — | API Server CA；集群内不设则用 ServiceAccount 的 `ca.crt` |
| `K8S_VERIFY_TLS` | `1` | `0` 仅本机调试 |
| `K8S_ALLOWED_NAMESPACES` | 空（不限制） | **生产必填**，逗号分隔；任务的 `k8s_spec.namespace` 必须在名单内 |
| `K8S_MAX_WAIT_SECONDS` | `3600` | 单个 Job 等待硬上限。实际等待 = `min(任务 Job 超时, 该值)` |
| `K8S_POLL_INTERVAL` | `3` | 轮询 Job 状态间隔（秒） |
| `K8S_JOB_TTL_SECONDS` | `120` | Job 结束后由 K8s 回收的 TTL |
| `K8S_DEADLINE_PADDING` | `100` | `activeDeadlineSeconds = 等待秒数 + 该值`，让 schedule-job 先判超时 |
| `K8S_LOG_TAIL_LINES` | `50` | 写入执行记录的 Pod 日志尾部行数；完整日志留在集群 |
| `K8S_REQUIRE_TIMEOUT` | `1` | `1` 时拒绝 `timeout=0` 的 K8s 任务 |
| `K8S_API_TIMEOUT` | `15` | 单次 Kubernetes HTTP 调用超时 |
| `CRON_K8S_WORKER_LIFE_TIME` | `86400` | K8s Worker 进程寿命（秒）；到期 reboot 会等在跑的协程结束 |
| `CRON_K8S_MAX_CONCURRENCY` | `1000` | 本节点同时等待中的 Job 数上限（≈ 占用协程数） |

完整注释见 `App/.env.example`。

### 4. 在管理台创建任务

1. **Cron Nodes** 建好 K8s Agent 节点，Agent `.env` 写入对应 `CRON_NODE_ID` / `CRON_NODE_API_KEY`。
2. 启动 Agent：`php cron.php start App`。`schedule-k8s-task-cron` 会和 fork / url Worker 一起拉起。
3. **计划任务** 里新建任务，执行类型选 **Kubernetes**，节点必须指向第 1 步那台 Agent。绑错节点会「保存成功但永远不执行」。
4. 填写 `k8s_spec`：

| 字段 | 说明 |
|------|------|
| `namespace` | 目标 Namespace，必须在 `K8S_ALLOWED_NAMESPACES` 内 |
| `deployment` | 用作模板的 Deployment 名 |
| `container` | `spec.template.spec.containers[].name`，多容器时必填 |
| `command` / `args` | argv 数组，覆盖目标容器；**禁止**写成带空格的一行或 `sh -c "..."` |

镜像、环境变量、卷**不保存在任务里**。到点即时 GET Deployment 模板再派生 Job，发布系统只更新 Deployment 即可。

5. **Job 超时（秒）必填**。Agent 用一个协程等 Job 结束，没有上限会一直占着。到期会删除该 Job（连带 Pod）并记为 TIMEOUT。需要按自身业务评估这个时间，长时间任务必须设够大。
6. 先用较小超时点「立即执行」验证：执行记录的 `task_item` 里应出现 `k8s_job_name` / `k8s_pod_name`，再放开 Cron 表达式。

`command` 列对 type 3 只是展示摘要，不是执行来源。

### 5. 行为约定

- Job 名：`sj-{execBatchId}-a{attempt}`；`backoffLimit: 0`，业务重试由 schedule-job 自己做（最多再试一次）。
- Job **不复制** Deployment 的 Service selector（避免把流量打到 Cron Pod），并剥离 probe / lifecycle / Istio 注入 / hostPort。
- 取消：Admin 只 CAS `CANCEL_REQUESTED`，Agent 先 DELETE Job 再收尾。
- Agent 崩溃且 Job 还在跑，或集群 API 暂时不可达：不删 Job、不改执行记录，下一轮再收割；Agent 再也没起来则跑到 `activeDeadlineSeconds` 后由 K8s 停掉。
- 完整 Pod 日志留在集群；`cron_task_log.message` 只写尾部摘要。

### 6. 本机 Docker Desktop 联调

```bash
kubectl apply -f deploy/kubernetes/local/docker-desktop.yaml
kubectl -n swoolefy get pods,svc,sa
kubectl create token schedule-job-agent -n swoolefy --duration=8760h
```

清单使用 Namespace `swoolefy`（不要用 `kube-public`），本机镜像 `swoolefy-php85-swoole62:v1`（`imagePullPolicy: Never`）。Agent `.env` 示例：

```env
K8S_API_SERVER=https://127.0.0.1:6443
K8S_VERIFY_TLS=0
K8S_ALLOWED_NAMESPACES=swoolefy
K8S_TOKEN=<上一步 token>
```

---

## 权限与数据范围

### 认证

- 管理 API 需 Header：`Authorization: Bearer <jwt>`
- 登录：`POST /api/v1/auth/login`
- **公开注册已关闭**：新用户由管理员在 **用户管理** 中创建（`POST /api/v1/users`）
- 默认超级管理员：`admin` / `123456789`（导入 `migrations/permission.sql` 后可用，生产环境请立即改密）

### 内置角色

| code | 说明 |
|------|------|
| `super_admin` | 超级管理员，不受节点组限制 |
| `editer_task_group` | 可编辑任意可见任务（不限创建人） |

### 数据范围

- 普通用户通过 **节点组授权**（`staff_user_relate_node_group`）限定可见的节点、任务、日志
- 任务创建人默认可管理自己创建的任务；`editer_task_group` 与超管可管理他人任务
- 菜单与 API 访问受角色-页面权限控制（`MenuPagePermissionMiddleware`）

---

## API 概览

基础前缀：`/api/v1`（管理端默认 `http://127.0.0.1:9502`）

### 认证

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/auth/login` | 登录 |
| GET | `/auth/me` | 当前用户 |

### 任务

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/tasks` | 分页列表 |
| GET | `/tasks/creators` | 创建人筛选项 |
| POST | `/tasks` | 创建 |
| PUT | `/tasks` | 更新 |
| DELETE | `/tasks` | 删除 |
| POST/PUT | `/tasks/status` | 启停 |
| PUT | `/tasks/batch-status` | 批量启停 |
| GET | `/tasks/detail` | 详情 |
| POST | `/tasks/run` | 手动执行 |
| POST | `/tasks/duplicate` | 复制任务 |
| POST | `/tasks/expression/preview` | 表达式预览 |
| GET | `/tasks/execution` | 单次执行详情 |
| GET | `/tasks/logs` | 执行日志分页 |
| GET | `/tasks/stats` | 任务统计 |
| GET | `/tasks/operation-logs` | 操作审计 |
| GET | `/tasks/operation-logs/operators` | 操作人筛选项 |

### 节点

| 方法 | 路径 | 说明 |
|------|------|------|
| GET/POST/PUT/DELETE | `/nodes` | 节点 CRUD |
| GET/POST/PUT/DELETE | `/node-groups` | 节点分组 CRUD（PUT 可选 `robotId`） |

### 机器人告警

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/robots` | 列表（Webhook 脱敏） |
| GET | `/robots/detail` | 详情（脱敏） |
| POST | `/robots` | 创建（超管） |
| PUT | `/robots` | 更新（超管；空 webhook/secret 表示不改） |
| DELETE | `/robots` | 软删（超管；仍被节点组引用则 409） |
| PUT | `/robots/status` | 启用 / 禁用（超管） |
| POST | `/robots/test` | 连通测试（超管） |

### Dashboard

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/dashboard/overview` | 概览 |
| GET | `/dashboard/execution-trend` | 执行趋势 |
| GET | `/runtime/overview` | Runtime 概览 |

### Agent（无需 JWT，需 apiKey）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/agent/tasks` | 拉取任务列表 |
| POST | `/agent/heartbeat` | 心跳 |
| POST | `/agent/report` | 状态上报 |

### 任务字段要点

| 字段 | 说明 |
|------|------|
| `name` | 任务名称（唯一） |
| `expression` | Cron 表达式 |
| `command` | Shell 命令、HTTP URL，或 type 3 的展示摘要（不是执行来源） |
| `exec_type` | `1`=Shell，`2`=HTTP，`3`=Kubernetes |
| `k8s_spec` | type 3：`namespace` / `deployment` / `container` / `command[]` / `args[]`；不存 image |
| `node_id` | 绑定 Agent 节点（type 3 必须是具备集群凭证的那台） |
| `status` | `0` 禁用，`1` 启用 |
| `with_block_lapping` | `1` 时阻塞重叠执行 |
| `retry` | 失败后额外重试次数（K8s 硬上限：最多再试一次） |
| `timeout` | type 3 必填且 `>0`，按业务评估；长任务要设够大 |
| `http_method` / `http_body` / `http_headers` | HTTP 任务专用 |

更完整的请求示例见 `App/Module/Cron/Controller/CronTaskManagerController.php` 中各方法的 curl 注释。

---

## 数据库表说明

| 表名 | 说明 |
|------|------|
| `cron_task` | 定时任务定义 |
| `cron_agent_node` | Agent 节点（含 `api_key`、心跳） |
| `cron_agent_node_group` | 节点分组（含 `robot_id`） |
| `cron_robot` | 群机器人（企微/钉钉/飞书 Webhook） |
| `cron_robot_alert_log` | 告警投递记录（每条 Execution 最多一行） |
| `cron_task_run_request` | 手动执行请求队列 |
| `cron_task_log` | 执行记录 |
| `cron_scheduled_task_record` | 调度点抢占（同一 cron_id + 时间点只有一个赢家） |
| `cron_task_operation_log` | 操作审计 |
| `staff_user` | 用户 |
| `staff_roles` | 角色 |
| `staff_menu_pages` | 菜单页面 |
| `staff_role_page` | 角色-页面 |
| `staff_user_role` | 用户-角色 |
| `staff_user_relate_node_group` | 用户-节点组 |

迁移脚本：`migrations/cron.sql`（新库）、`migrations/upgrade_kubernetes_exec_type.sql`（已有库补 `exec_type=3` / `k8s_spec`）、`migrations/robot_alert.sql`（已有库升级告警）。

---

## 目录结构

```
schedule-job/
├── App/
│   ├── Config/                 # 应用配置（db、auth、health…）
│   ├── Controller/             # 公共控制器
│   ├── Module/
│   │   ├── Cron/               # Cron 管理模块
│   │   │   ├── Controller/     # API / 静态资源
│   │   │   ├── Service/        # 业务逻辑
│   │   │   ├── Entity/         # 数据实体
│   │   │   ├── Dto/            # 数据传输对象
│   │   │   └── static/         # Web 前端静态文件
│   │   └── Staff/              # 用户 / 角色 / 权限
│   ├── Process/                # 自定义 Swoole 进程
│   │   └── PurgeExpiredTaskLogs.php  # 过期日志清理
│   ├── Router/                 # 路由定义
│   │   └── Module/
│   │       ├── CronManager.php
│   │       └── StaffManager.php
│   ├── WorkerCron/             # Agent Worker 配置
│   │   ├── MainCronProcess.php
│   │   └── conf/
│   ├── Event.php               # 应用生命周期钩子
│   └── .env                    # 环境变量（不提交 Git）
├── migrations/                 # SQL 迁移
├── deploy/kubernetes/          # Agent RBAC 与本机 Docker Desktop 清单
├── cli.php                     # HTTP 管理服务入口
├── cron.php                    # Cron Worker 入口
├── composer.json
└── README.md
```

---

## 运维说明

### 执行日志清理

`App/Process/PurgeExpiredTaskLogs` 随 HTTP 管理服务启动（见 `App/Event.php`）：

- 启动后立即执行一次
- 之后每 **12 小时**执行一次
- 硬删除 `created_at` 早于 `CRON_TASK_LOG_DELETE_DAY` 天的 `cron_task_log` 记录

### 进程与日志路径

Swoolefy 默认 PID / 控制日志目录：

```
/tmp/workerfy/log/<service-name>/
```

### 生产建议

- 修改 `AUTH_JWT_SECRET`，开启 `ENABLE_LOG_SANITIZE`
- 首次部署后立即修改默认超管密码（`admin` / `123456789`）
- Admin 与 Agent 使用同一 MySQL，Agent 仅需读任务 + 写日志/心跳
- 为 `cron_task_log.created_at` 保留合理天数，避免表过大
- 使用 `--daemon=1` + 进程监控（systemd / supervisord / K8s）
- 配置 `/health`、`/ready` 探针
- 跑 Kubernetes 任务的 Agent：填写 `K8S_ALLOWED_NAMESPACES`，按 Namespace 应用 `deploy/kubernetes/schedule-job-agent-rbac.yaml`，任务绑到该节点

---

## 常见问题

**Q: 管理台能打开，但任务一直不执行？**  
A: 确认 Agent Worker（`cron.php`）已启动；`CRON_NODE_ID` / `CRON_NODE_API_KEY` 与 Admin 中节点一致；任务 `node_id` 匹配且 `status=1`。

**Q: Agent 拉任务报「节点不存在或凭证无效」？**  
A: 检查 `apiKey` 是否与 `cron_agent_node.api_key` 一致；节点未被软删。

**Q: 修改 `.env` 后不生效？**  
A: 重启对应服务：`php cli.php restart App` 或 `php cron.php restart App`。

**Q: 非超管看不到任务？**  
A: 在用户管理中为用户授权对应 **节点组**，且任务所属节点在该组内。

**Q: 静态资源 404？**  
A: 新增前端 JS/CSS 需加入 `CronAdminController` 的白名单 `$allowed`。

**Q: Kubernetes 任务保存成功但不执行？**  
A: 任务 `node_id` 必须指向正在跑、且 `.env` 配了集群凭证的那台 Agent。Agent 按 `node_id + exec_type` 拉任务，绑到普通 Shell 节点永远不会 Create Job。

**Q: Kubernetes 任务到点后集群里没有 Job？**  
A: 确认 `schedule-k8s-task-cron` 已随 `php cron.php start App` 拉起；`K8S_ALLOWED_NAMESPACES` 包含任务 namespace；Deployment / container 名称与集群一致。执行记录失败原因常见为 `KUBERNETES_DEPLOYMENT_NOT_FOUND`。

---

### License

MIT  
Copyright (c) 2017-2026 zengbing huang
