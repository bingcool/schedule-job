# schedule-job 节点组机器人告警技术方案

> 版本：v5（对照现有代码修订；SQL 以本文第 5 节为准）  
> 状态：已落地实现  
> 目标：节点组任务执行 `FAILED` / `TIMEOUT` 时，向该组绑定的企微 / 钉钉 / 飞书群机器人发告警。  
> 原则：**不改 Execution 状态机；不引入 MQ；告警是旁路；SQL 与现有 `cron_*` 表风格对齐。**

---

## 0. 相对 v3 的修订结论

v3 方向正确：Robot 全局资源、NodeGroup 只存 `robot_id`、CAS 保证最多一次触发、Webhook 必须超时、Strategy 隔离平台。

**不在 `cron_task_log` 上快照 `robot_id`。** 告警发送时按 Execution 的 `node_id` 找到节点组，读取 **当时** 的 `cron_agent_node_group.robot_id`。管理员中途改绑定，已在跑的任务失败后会打到新机器人；这是明确接受的取舍。

对照当前仓库后，以下设计不合理，已改：

| 问题 | v3 | 现行 |
|------|----|----|
| 路由 | `/api/cron/robots` | 现有前缀 **`/api/v1`** |
| 事件总线 | `ExecutionFinishedEvent` 独立总线 | 项目无业务 Event 总线；CAS 成功后 **`go()` 协程** 调 `CronAlertService` |
| Repository | `CronRobotRepository` | 现有是 Entity + Service，不新增 Repository |
| 审计 | 塞进「现有操作审计」 | `cron_task_operation_log` 只服务计划任务，**禁止写入**；机器人变更用 `created_by` / `updated_by` + 测试字段 |
| 加密 | 库内 AES | 项目无统一加密组件；库内明文 + **API 脱敏**；加密留后续 |
| `cron_robot.uk_name` | UNIQUE(name) | 与软删冲突（删后再建同名会 1062）；**应用层**对 `deleted_at IS NULL` 唯一，库上只建普通索引 |
| 测试成功时间混用 | `last_success_at` 既测又发 | 机器人表只记 **测试**；真实投递只写 `cron_robot_alert_log` |
| alert_log `delete_at` | 有软删 | 投递记录追加写，**不软删** |
| `attempt` + pending | 像要做重试队列 | 第一版不重试；插入终态一行 |
| `cron_task_log.robot_id` 快照 | Execution 创建时写入 | **不需要该列**；发送时读节点组当前 `robot_id` |
| TIMEOUT 一律告警 | 是 | **`failure_reason=CANCELLED` 不告警**（取消杀进程可能走过 TIMEOUT 路径） |
| Metrics | P1 Prometheus | 项目无现成 Cron 指标管道；第一版不做 |
| 权限 | 自造 CREATE/TEST 枚举 | 沿用菜单 `staff_role_page` + **写操作再校验超级管理员** |

---

## 1. 产品范围

当节点组内任务执行终态为 **FAILED** 或 **TIMEOUT**（且非取消）时，向该节点组绑定的群机器人发一条告警。

支持平台：企业微信、钉钉、飞书。每个节点组最多绑定 **一个** 机器人；一个机器人可被多个节点组复用。

不发送：`SUCCESS` / `SKIPPED` / `CANCELLED` / `cancel_requested` / 配置变更日志（无 `exec_batch_id`）。

Worker Crash Recovery 把 RUNNING 收成 `FAILED` + `WORKER_CRASH`，走同一条告警旁路。

---

## 2. 架构

```text
系统设置 / 机器人告警
        │
        ▼
   cron_robot（全局）
        ▲
        │ robot_id（0=不告警）
cron_agent_node_group
        ▲
        │ group_id
cron_agent_node
        ▲
        │ node_id
cron_task_log
        │
        │ Terminal CAS affected=1
        │ 且 status ∈ {FAILED, TIMEOUT}
        │ 且 failure_reason ≠ CANCELLED
        ▼
   go() 隔离协程
        ▼
  CronAlertService
        │ node → group.robot_id
        │ robot_id=0 / 无分组 → return（不写 log）
        │ robot 禁用/删除 → 写 alert_log skipped
        ▼
  RobotAlertMessage → RobotStrategyFactory
        │ wecom / dingtalk / feishu
        ▼
  HTTP Webhook（connect/request timeout）
        ▼
  cron_robot_alert_log（每 Execution 最多 1 行，UNIQUE execution_id）
```

告警失败不得回写 `cron_task_log.status`。

---

## 3. 产品入口

### 3.1 菜单

新增一级分组与页面（写入 `staff_menu_pages`，超级管理员自动可见）：

```text
系统设置          uri=/system   （分组占位）
└── 机器人告警    uri=/robots
```

`StaffMenuPermissionService::API_PATH_MENU_RULES` 增加 `/api/v1/robots` → `/robots`。

列表能力：新增 / 编辑 / 删除 / 启用 / 禁用 / 测试；展示平台、状态、最近测试结果（脱敏 webhook）。

### 3.2 节点组

复用现有 `PUT /api/v1/node-groups`，增加可选字段 `robotId`：

- `0`：不使用机器人
- `>0`：绑定已存在、未删除、**已启用** 的机器人，否则 422

节点组列表操作列增加「机器人告警」入口，本质仍是更新该字段。不新增无意义的独立 SET/REMOVE API。

---

## 4. 权限

| 操作 | 菜单 `/robots` | 额外校验 |
|------|----------------|----------|
| 列表 / 详情（脱敏） | 有菜单即可 | 无 |
| 新增 / 改 webhook·secret / 删除 / 启用禁用 / 测试 | 有菜单 | **必须超级管理员** |
| 节点组绑定 `robotId` | 有节点组管理菜单（现有 `/nodes` 体系） | **必须超级管理员**（webhook 等同密钥） |

Service 层必须再判 `isSuperUser`，不能只靠前端藏按钮。

---

## 5. 数据模型与 SQL

新增列必须带 `COMMENT`。索引是否带 `COMMENT` 以本节 SQL 为准（不另行补索引注释）。类型、时间字段、软删列名与现有 `cron_*` 表对齐：`deleted_at`（不用 staff 表的 `delete_at`）。

不建物理外键（与现库一致），关联在应用层维护。

### 5.1 `cron_robot`

```sql
CREATE TABLE `cron_robot` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '机器人名称，未删除范围内应用层唯一',
    `platform` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1-企业微信wecom 2-钉钉dingtalk 3-飞书feishu',
    `webhook_url` varchar(2048) NOT NULL DEFAULT '' COMMENT 'Webhook 完整地址（含 key），敏感，API 必须脱敏',
    `secret` varchar(512) NOT NULL DEFAULT '' COMMENT '签名密钥，可空（企微通常不需要）；API 不回明文',
    `config_json` json DEFAULT NULL COMMENT '平台扩展配置',
    `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '0-禁用 1-启用',
    `last_test_at` datetime DEFAULT NULL COMMENT '最近一次点「测试」的时间',
    `last_test_ok` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '最近测试是否成功：0-否/未测 1-是',
    `last_test_error` varchar(1000) NOT NULL DEFAULT '' COMMENT '最近测试失败原因，成功则清空',
    `created_by` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人 staff_user.id',
    `updated_by` int unsigned NOT NULL DEFAULT '0' COMMENT '最后修改人 staff_user.id',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    `deleted_at` datetime DEFAULT NULL COMMENT '软删时间',
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`),
    KEY `idx_platform_status` (`platform`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron群机器';
```

说明：

- **不设 `access_token` 列。** 钉钉/飞书签名用 `secret`；企微 key 在 URL 里。
- **不把真实投递成功/失败写进本表**（避免测试与告警互相覆盖）。投递只看 `cron_robot_alert_log`。
- `platform` 用 tinyint，与 `exec_type` / `status` 风格一致；PHP 常量：`WeCom=1 Dingtalk=2 Feishu=3`。

### 5.2 `cron_agent_node_group.robot_id`

```sql
ALTER TABLE `cron_agent_node_group`
    ADD COLUMN `robot_id` bigint unsigned NOT NULL DEFAULT 0
        COMMENT '绑定的 cron_robot.id；0=该组不发告警' AFTER `group_name`,
    ADD KEY `idx_robot_id` (`robot_id`);
```

绑定校验：`id 存在 AND deleted_at IS NULL AND status=1`，否则 422。

`cron_task_log` **不加** `robot_id`。发送告警时现查：

```text
cron_task_log.node_id
  → cron_agent_node.group_id
  → cron_agent_node_group.robot_id
  → cron_robot（当前 webhook / secret / status）
```

无分组、`robot_id=0`：不发送、不写 `cron_robot_alert_log`。

### 5.3 `cron_robot_alert_log`

第一版：**每条 Execution 最多一行**；无 pending；不软删；不重试。

```sql
CREATE TABLE `cron_robot_alert_log` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `execution_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT 'cron_task_log.id',
    `cron_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT 'cron_task.id',
    `exec_batch_id` varchar(64) NOT NULL DEFAULT '' COMMENT '执行批次，与 cron_task_log.exec_batch_id 一致',
    `robot_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '发送时使用的机器人',
    `platform` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '发送时平台快照：1-wecom 2-dingtalk 3-feishu',
    `alert_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1-FAILED 2-TIMEOUT',
    `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1-发送成功 2-发送失败 3-跳过（机器人禁用/已删）',
    `skip_reason` varchar(32) NOT NULL DEFAULT '' COMMENT '跳过原因：robot_disabled/robot_not_found；成功或失败为空',
    `http_status` smallint NOT NULL DEFAULT 0 COMMENT 'Webhook HTTP 状态码，未发出为 0',
    `error_message` varchar(1000) NOT NULL DEFAULT '' COMMENT '失败/跳过原因说明，禁止写入 webhook URL 或 secret',
    `sent_at` datetime DEFAULT NULL COMMENT 'Webhook 调用结束时间（成功或失败都写)',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_execution_id` (`execution_id`),
    KEY `idx_robot_created` (`robot_id`, `created_at`),
    KEY `idx_cron_created` (`cron_id`, `created_at`),
    KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cron机器人告警投递记录';
```

`robot_id=0`：**不插入** 本表（本来就没配置告警，避免噪声）。

并发：Terminal CAS 只有一方 `affected=1` 才 `go()`；若协程仍被跑两次，第二次 INSERT 撞 `uk_execution_id` 则忽略。

---

## 6. 删除 / 禁用机器人

**删除：** `COUNT(*) FROM cron_agent_node_group WHERE robot_id=?` > 0 则 409：「该机器人被 N 个节点组使用，请先在节点组中解除」。解除后软删 `deleted_at`。

**禁用：** 只改 `status=0`，不改各节点组 `robot_id`。发送时发现禁用 → alert_log `status=3 skip_reason=robot_disabled`。

已删除：发送时找不到机器人 → `status=3 skip_reason=robot_not_found`。Execution 仍是 FAILED/TIMEOUT。

---

## 7. 触发与旁路（对接现有 CAS）

项目没有可订阅的 Execution Event 总线。落地方式：

在 `ExecutionService` **所有终态 CAS 成功** 的出口调用同一方法，例如 `AlertDispatcher::dispatchIfNeeded($logId)`：

- `updateExecution` 合法转移到 FAILED/TIMEOUT
- `finishOwned`（超时/取消杀进程）
- `recoverRow`（租约过期 WORKER_CRASH）
- `agentReport` 走到终态且 CAS 成功

内部：

```text
affected !== 1  → return
status ∉ {FAILED, TIMEOUT} → return
failure_reason === CANCELLED → return
go(function () { try { CronAlertService::send($logId); } catch (\Throwable) { 只记应用日志 } })
  内部再查 node → group.robot_id，为 0 则 return
```

`go()` 在 Worker 返回之后跑，Webhook 阻塞不影响 Execution 闭合。无 Swoole 环境（单测）则同步调用但仍 catch，不得抛回状态机。

禁止：CAS 成功后同步 HTTP；禁止告警异常改 status。

---

## 8. 消息与 Strategy

`CronAlertService` 组 `RobotAlertMessage`，Strategy **禁止再查** `cron_task` / log。

建议字段：

```text
executionId, cronId, execBatchId, taskName
nodeId, nodeName, nodeGroupId, nodeGroupName
status, failureReason
scheduledAt, startedAt, finishedAt, durationMs
exitCode, httpStatus, triggerType
command   // Shell 命令或 HTTP URL，截断 200 字
```

不把 webhook、secret、lease_owner 放进消息。

```text
RobotStrategyInterface::send(RobotConfig, RobotAlertMessage): RobotSendResult
WeComRobotStrategy / DingTalkRobotStrategy / FeishuRobotStrategy
RobotStrategyFactory::make(platform): match 1/2/3
```

签名：钉钉/飞书用 `secret` 算 sign；`secret=''` 则不签名。超时集中读取：

```text
ROBOT_CONNECT_TIMEOUT   不设置默认 10
ROBOT_REQUEST_TIMEOUT   不设置默认 20
WEBHOOK_HOST_WECOM      不设置默认 qyapi.weixin.qq.com
WEBHOOK_HOST_DINGTALK   不设置默认 oapi.dingtalk.com
WEBHOOK_HOST_FEISHU     不设置默认 open.feishu.cn,open.larkoffice.com
```

写入 `App/.env.example`，不要三个 Strategy 各写一套数字。

---

## 9. API（`/api/v1`）

```text
GET    /api/v1/robots
GET    /api/v1/robots/detail?id=
POST   /api/v1/robots
PUT    /api/v1/robots
DELETE /api/v1/robots
POST   /api/v1/robots/test
PUT    /api/v1/robots/status          // enable/disable，与任务 status 接口风格一致
```

节点组：

```text
PUT /api/v1/node-groups     // body 增加可选 robotId
```

列表/详情：`webhookUrl` 打码（保留 scheme/host，query 中 key 变 `******`）；`secret` 只返回是否已配置布尔，不回明文。更新 secret：空字符串表示不改，有值才覆盖。

测试：不创建 Execution；发「机器人连通测试」文案（禁止做成 FAILED 告警样式）。结果只更新 `last_test_*`。

---

## 10. 敏感信息

`webhook_url` / `secret` / `config_json`：

- API 永不回完整 secret、完整 webhook query
- alert_log.error_message 禁止带 URL/secret
- 第一版库内明文；若后续加密，密钥走 env，迁移另开

---

## 11. 明确不做（第一版）

```text
MQ / 独立 Alert Worker
告警聚合、降噪、升级、恢复通知
一节点组多机器人
alert_log 重试 / pending 队列
Prometheus 指标
AES 落库
CronRobotRepository
写入 cron_task_operation_log
```

可靠投递、恢复告警、`robot_ids[]` 留后续演进。

---

## 12. 测试要点

1. 非超管 POST robots / 改 robotId → 403；有菜单可看脱敏列表。
2. 绑定禁用或不存在的 robot → 422；`robotId=0` 保存成功且后续不告警、不写 alert_log。
3. 执行开始时 group.robot_id=1，中途改成 2，失败打 **机器人 2**（发送时读当前绑定）。
4. 发送时 `robot_id=0` / 无分组 → 不告警、不写 alert_log；发送时机器人已禁用 → alert_log `status=3 skip_reason=robot_disabled`。
5. 两个 CAS 抢 FAILED → 仅一次 go()；alert_log 一行。
6. Webhook timeout / 500 / 业务码失败 → alert_log.status=2，Execution 仍 FAILED。
7. Recovery WORKER_CRASH → 告警；用户取消导致的闭合 → 不告警。
8. 删除仍被节点组引用 → 409。

---

## 13. 实施顺序

**P0 库表一次做完**（分组绑定、投递都依赖 `cron_robot` 存在）：

1. `cron_robot` + `node_group.robot_id` + `cron_robot_alert_log`（**不加** `cron_task_log.robot_id`）
2. CAS 成功出口 `go(CronAlertService)`（发送时查 node → group.robot_id；可先打日志，Strategy 随后补）

**P1 管理面与发送：**

3. 菜单 `/system` `/robots` + CRUD/测试/脱敏 + 超管校验  
4. 节点组 `robotId`  
5. 三个 Strategy + timeout  
6. 权限与 CAS / 取消 / Crash / 发送时绑定变更用例

---

## 14. 设计原则（落地检查清单）

1. Robot 全局；NodeGroup 只存一个 `robot_id`。  
2. **不**在 `cron_task_log` 快照 `robot_id`；发送时读节点组当前绑定。  
3. 状态机不依赖 Robot；告警在 CAS 之后协程旁路。  
4. CAS 负责「最多触发一次」；`uk_execution_id` 负责「最多一行投递记录」。  
5. 投递结果与 Execution.status 独立。  
6. Webhook 必须有连接/请求超时。  
7. Strategy 不查库。  
8. 不引入 MQ / Repository / 任务操作审计表。  
9. 写 webhook 与绑定节点组：超级管理员。  
10. SQL 以第 5 节为准：新增列必须 `COMMENT`；索引按该节 DDL，不额外补注释。
