# schedule-job 节点组机器人告警技术方案（优化版）

> 版本：v3  
> 状态：设计方案  
> 目标：在现有 schedule-job Cron / Execution 架构基础上，为节点组增加企业微信、钉钉、飞书群机器人告警能力。  
> 核心原则：**不改造 Execution 核心状态机、不引入 MQ、不做过度设计；以现有 CAS、NodeGroup、Execution 体系为基础增加告警旁路。**

---

## 1. 背景

当前任务执行已经具备：

- Execution 生命周期状态管理
- Execution 状态 CAS
- Worker Crash Recovery
- Timeout / Cancel
- Node / NodeGroup
- Execution 日志
- 审计与 Metrics

现在增加：

> 当节点组中的任务执行 `FAILED` 或 `TIMEOUT` 时，向该节点组配置的群机器人发送告警。

支持：

- 企业微信
- 钉钉
- 飞书

每个节点组最多选择一个机器人。

机器人作为系统级资源，可以被多个节点组复用。

---

# 2. 本次优化后的核心设计

最终采用：

```text
                        系统设置
                           │
                      机器人警告
                           │
                           ↓
                    ┌─────────────┐
                    │ cron_robot  │
                    │ 全局机器人   │
                    └──────┬──────┘
                           ↑
                           │ robot_id
                           │
                  cron_agent_node_group
                           │
                           ↓
                    Execution Register
                           │
                           │ snapshot
                           ↓
                  cron_task_log.robot_id
                           │
                           ↓
                   FAILED / TIMEOUT
                           │
                           ↓
                  Terminal CAS Success
                           │
                           ↓
                ExecutionFinishedEvent
                           │
                           ↓
                  CronAlertService
                           │
                           ↓
                 RobotAlertMessage
                           │
                           ↓
                RobotStrategyFactory
                  ┌────────┼────────┐
                  ↓        ↓        ↓
                 企微      钉钉      飞书
                           │
                           ↓
                 cron_robot_alert_log
```

本次重点优化：

1. `cron_task_log.robot_id` 快照
2. 增加 `cron_robot_alert_log`
3. 明确 Event 唯一性与 Delivery 的区别
4. Alert 不阻塞 Execution
5. Webhook timeout
6. 删除 `access_token`
7. 增加 `RobotAlertMessage` DTO

---

# 3. 产品入口

## 3.1 系统设置

新增一级菜单：

```text
系统设置
└── 机器人警告
```

进入后：

```text
机器人警告列表
```

支持：

- 新增机器人
- 编辑机器人
- 删除机器人
- 启用
- 禁用
- 测试机器人
- 查看平台
- 查看最近测试状态

---

## 3.2 节点组

在节点组列表操作列增加：

```text
机器人警告
```

点击：

```text
选择机器人
```

例如：

```text
节点组：生产环境

机器人：
[ 生产钉钉告警 ▼ ]

      [保存]
```

也可以选择：

```text
[ 不使用机器人 ]
```

对应：

```text
robot_id = 0
```

---

# 4. 权限

机器人配置属于系统级敏感配置。

## 4.1 超级管理员

允许：

```text
机器人：
CREATE
UPDATE
DELETE
ENABLE
DISABLE
TEST

节点组：
SET_ROBOT
REMOVE_ROBOT
```

## 4.2 普通管理员

允许：

```text
VIEW
```

禁止：

```text
CREATE
UPDATE
DELETE
TEST
SET_ROBOT
REMOVE_ROBOT
```

后端 Service 层必须再次校验超级管理员权限。

不能只依赖前端按钮隐藏。

---

# 5. 数据模型

核心关系：

```text
cron_robot
     ↑
     │ robot_id
     │
cron_agent_node_group
```

机器人不保存：

```text
group_id
```

原因：

```text
Robot = 系统级资源
NodeGroup = 使用方
```

因此：

```text
Robot A
   ↑
   ├── 生产节点组
   ├── 灾备节点组
   └── 测试节点组
```

一个机器人可以被多个节点组复用。

---

# 6. cron_robot

建议：

```sql
CREATE TABLE `cron_robot` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '机器人名称',
    `platform` varchar(32) NOT NULL DEFAULT '' COMMENT '平台：wecom/dingtalk/feishu',
    `webhook_url` varchar(1000) NOT NULL DEFAULT '' COMMENT 'Webhook地址，敏感信息',
    `secret` varchar(512) NOT NULL DEFAULT '' COMMENT '签名Secret，密文存储',
    `config_json` json DEFAULT NULL COMMENT '平台扩展配置',
    `status` tinyint NOT NULL DEFAULT 1 COMMENT '0禁用 1启用',
    `last_test_at` datetime DEFAULT NULL COMMENT '最近测试时间',
    `last_success_at` datetime DEFAULT NULL COMMENT '最近成功时间',
    `last_failure_at` datetime DEFAULT NULL COMMENT '最近失败时间',
    `last_error` varchar(1000) NOT NULL DEFAULT '' COMMENT '最近错误',
    `created_by` bigint unsigned NOT NULL DEFAULT 0,
    `updated_by` bigint unsigned NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`),
    KEY `idx_platform_status` (`platform`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Cron群机器人配置';
```

## 6.1 删除 access_token

不再设计：

```text
access_token
```

第一版只保留：

```text
webhook_url
secret
config_json
```

原因：

- DingTalk / Feishu 的机器人认证信息已经包含在 Webhook / Secret 语义中
- 不需要提前拆出平台特定字段
- 避免 cron_robot 表不断增加平台专属字段

如果未来平台确实存在额外配置，放入：

```text
config_json
```

---

# 7. NodeGroup.robot_id

增加：

```sql
ALTER TABLE `cron_agent_node_group`
ADD COLUMN `robot_id` bigint unsigned NOT NULL DEFAULT 0
COMMENT '告警机器人ID，0表示未配置';

ALTER TABLE `cron_agent_node_group`
ADD KEY `idx_robot_id` (`robot_id`);
```

业务语义：

```text
robot_id = 0
    ↓
不发送告警

robot_id > 0
    ↓
使用指定机器人
```

设置时校验：

```text
robot exists
robot not deleted
robot enabled
```

---

# 8. Execution robot_id 快照 —— P0

这是本次优化最重要的一项。

不能在 Execution 结束时重新查询：

```text
NodeGroup.robot_id
```

否则会产生历史一致性问题。

例如：

```text
10:00
Execution A 开始
NodeGroup.robot_id = 1

10:01
管理员把 NodeGroup.robot_id 改成 2

10:02
Execution A FAILED
```

如果失败时重新读取 NodeGroup：

```text
Execution A
    ↓
NodeGroup
    ↓
robot_id = 2
```

那么 Execution A 会错误地发送到机器人 2。

因此：

> Execution 创建时必须快照当时的 robot_id。

---

## 8.1 cron_task_log.robot_id

增加：

```sql
ALTER TABLE `cron_task_log`
ADD COLUMN `robot_id` bigint unsigned NOT NULL DEFAULT 0
COMMENT '执行创建时快照的告警机器人ID';

ALTER TABLE `cron_task_log`
ADD KEY `idx_robot_id` (`robot_id`);
```

---

## 8.2 快照时机

Execution register 时：

```text
cron_task
   ↓
node
   ↓
node_group
   ↓
node_group.robot_id
   ↓
cron_task_log.robot_id
```

之后 Execution 生命周期内：

```text
不再重新读取 NodeGroup.robot_id
```

最终：

```text
Execution
    ↓
cron_task_log.robot_id
    ↓
cron_robot
```

这样机器人配置修改不会影响已经开始执行的任务。

---

# 9. 机器人删除与禁用

## 9.1 删除

机器人可能被多个节点组使用。

删除前：

```sql
SELECT COUNT(*)
FROM cron_agent_node_group
WHERE robot_id = ?;
```

如果：

```text
count > 0
```

返回：

```text
409 Conflict
```

提示：

```text
该机器人当前被 N 个节点组使用，请先解除关联。
```

解除所有关联后允许软删除：

```sql
UPDATE cron_robot
SET deleted_at = NOW()
WHERE id = ?;
```

---

## 9.2 禁用

禁用：

```text
status = 0
```

不自动修改：

```text
node_group.robot_id
```

新的 Execution 仍然可以快照该 robot_id。

但发送告警时发现：

```text
robot.status = 0
```

则：

```text
跳过发送
```

记录：

```text
reason = robot_disabled
```

这样不会因为机器人临时禁用而修改大量节点组配置。

---

# 10. RobotAlertMessage DTO —— P1

Strategy 不应该自己查询业务数据。

统一由：

```text
CronAlertService
```

构造：

```text
RobotAlertMessage
```

建议字段：

```php
final class RobotAlertMessage
{
    public int $executionId;
    public int $taskId;
    public string $taskName;

    public int $nodeId;
    public string $nodeName;

    public int $nodeGroupId;
    public string $nodeGroupName;

    public string $status;
    public string $failureReason;

    public ?string $scheduledAt;
    public ?string $startedAt;
    public ?string $finishedAt;

    public ?int $durationMs;

    public ?int $exitCode;
    public ?int $httpStatus;

    public ?string $requestId;
}
```

Strategy 只负责：

```text
RobotAlertMessage
        ↓
平台消息格式
        ↓
Webhook
```

而不是：

```text
Strategy
   ↓
MySQL
   ↓
cron_task
   ↓
cron_task_log
   ↓
node
```

这样可以保持 Strategy 单一职责。

---

# 11. Strategy Pattern

统一接口：

```php
interface RobotStrategyInterface
{
    public function send(
        RobotConfig $robot,
        RobotAlertMessage $message
    ): RobotSendResult;
}
```

实现：

```text
RobotStrategyInterface
        │
        ├── WeComRobotStrategy
        ├── DingTalkRobotStrategy
        └── FeishuRobotStrategy
```

Factory：

```php
final class RobotStrategyFactory
{
    public function make(string $platform): RobotStrategyInterface
    {
        return match ($platform) {
            'wecom'    => new WeComRobotStrategy(),
            'dingtalk' => new DingTalkRobotStrategy(),
            'feishu'   => new FeishuRobotStrategy(),
            default    => throw new UnsupportedRobotPlatformException(),
        };
    }
}
```

业务层完全不关心具体平台。

---

# 12. RobotSendResult

统一返回：

```php
final class RobotSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly int $httpStatus = 0,
        public readonly string $errorMessage = '',
    ) {}
}
```

例如：

```text
DingTalk
    ↓
HTTP 200
    ↓
业务 code success
    ↓
RobotSendResult(success=true)
```

或者：

```text
HTTP timeout
    ↓
RobotSendResult(
    success=false,
    errorMessage="request timeout"
)
```

---

# 13. Webhook Timeout —— P1

所有 Strategy 必须设置：

```text
connect timeout
request timeout
```

不能使用无限等待。

建议默认：

```text
connect_timeout = 2s
request_timeout = 5s
```

实际数值集中配置，不要散落在三个 Strategy 中。

例如：

```text
CronRobotConfig

robot_connect_timeout
robot_request_timeout
```

如果第一版不开放后台配置，则使用系统配置：

```text
config/cron.php
```

即可。

---

# 14. Alert 不阻塞 Execution —— P0

这是核心原则。

错误方式：

```text
ExecutionService
    ↓
CAS FAILED
    ↓
HTTP Webhook
    ↓
等待
    ↓
Execution 完成
```

正确：

```text
ExecutionService
    ↓
CAS FAILED
    ↓
publish Event
    ↓
Execution 返回
```

然后：

```text
ExecutionFinishedEvent
       ↓
CronAlertListener
       ↓
Swoole Coroutine
       ↓
CronAlertService
       ↓
Webhook
```

因此：

> Robot Alert 是 Execution 的旁路能力，而不是 Execution 状态机的一部分。

机器人故障：

```text
Webhook timeout
Webhook 500
Webhook 连接失败
```

都不能改变：

```text
Execution.status
```

例如：

```text
Execution = FAILED
Robot Alert = FAILED
```

两个状态独立。

---

# 15. Event 唯一性 ≠ Delivery 唯一性 —— P0

必须区分：

```text
Event 唯一性
```

与：

```text
Delivery 结果
```

Terminal CAS 只负责：

```text
同一个 Execution
    ↓
最多发布一次 Alert Event
```

例如：

```sql
UPDATE cron_task_log
SET status = ?
WHERE id = ?
  AND status = ?
```

只有：

```text
affected_rows = 1
```

的一方发布：

```text
ExecutionFinishedEvent
```

因此：

```text
Worker A
RUNNING → FAILED
CAS success
    ↓
Event

Worker B
RUNNING → FAILED
CAS conflict
    ↓
No Event
```

但：

```text
Event
 ↓
Webhook
 ↓
可能成功
可能失败
可能 timeout
```

所以不能表述成：

```text
CAS → 最多一次实际告警
```

准确语义是：

```text
CAS
 ↓
最多一次 Alert Event
 ↓
Delivery 可以有独立结果
```

---

# 16. cron_robot_alert_log —— P1

为了记录 Delivery 状态，增加：

```sql
CREATE TABLE `cron_robot_alert_log` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `execution_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT 'Execution ID',
    `robot_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '机器人ID',
    `platform` varchar(32) NOT NULL DEFAULT '' COMMENT '平台',
    `alert_type` varchar(32) NOT NULL DEFAULT '' COMMENT 'FAILED/TIMEOUT',
    `status` tinyint NOT NULL DEFAULT 0 COMMENT '0->pending 1->success 2->failed',
    `attempt` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '发送次数',
    `http_status` smallint NOT NULL DEFAULT 0 COMMENT 'HTTP状态码',
    `error_message` varchar(1000) NOT NULL DEFAULT '' COMMENT '错误信息',
    `send_success_at` datetime DEFAULT NULL COMMENT '成功发送时间',
    `send_fail_at` datetime DEFAULT NULL COMMENT '失败时间',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `delete_at` datetime DEFAULT NULL COMMENT '删除|禁用时间',
    PRIMARY KEY (`id`),
    KEY `idx_execution_id` (`execution_id`),
    KEY `idx_robot_id_created` (`robot_id`, `created_at`),
    KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Cron机器人告警发送记录';
```

---

# 17. Alert Log 的意义

它解决：

```text
Execution FAILED
      ↓
Alert Event
      ↓
Webhook failed
```

之后无法判断：

```text
到底有没有发送？
为什么失败？
HTTP 状态？
是否 timeout？
发送了几次？
```

增加 Alert Log 后：

```text
Execution
    ↓
Alert Event
    ↓
cron_robot_alert_log
    ↓
Strategy
    ↓
SUCCESS / FAILED
```

可以查询：

```text
Execution #10001
机器人 #3
DingTalk
FAILED
attempt = 2
http_status = 500
error_message = ...
```

---

# 18. 第一版不引入 MQ

不建议为了机器人告警直接增加：

```text
RabbitMQ
Kafka
Redis Stream
独立 Alert Worker
```

第一版：

```text
Event
 ↓
Swoole Coroutine
 ↓
Webhook
 ↓
Alert Log
```

已经足够。

如果未来出现：

```text
大量告警
Webhook 堵塞
复杂重试
告警削峰
跨机器可靠投递
```

再演进：

```text
Execution Event
      ↓
Message Queue
      ↓
Alert Worker
      ↓
Robot
```

当前不提前设计。

---

# 19. Alert 触发条件

第一版：

```text
FAILED  → Alert
TIMEOUT → Alert
```

不发送：

```text
SUCCESS
CANCELLED
SKIPPED
```

Worker Crash Recovery：

```text
RUNNING
   ↓
Lease expired
   ↓
Recovery
   ↓
FAILED
failure_reason = WORKER_CRASH
   ↓
Alert
```

因此 Worker Crash 也自然进入统一告警流程。

---

# 20. 告警流程

```text
Execution
    │
    ├── SUCCESS
    │
    ├── CANCELLED
    │
    ├── SKIPPED
    │
    ├── FAILED
    │      ↓
    │   Terminal CAS
    │      ↓
    │   Event
    │
    └── TIMEOUT
           ↓
        Terminal CAS
           ↓
         Event
           │
           ↓
    CronAlertListener
           │
           ↓
    CronAlertService
           │
           ├── robot_id = 0
           │      ↓
           │    return
           │
           ├── robot deleted
           │      ↓
           │    record failure/skip
           │
           ├── robot disabled
           │      ↓
           │    record skip
           │
           └── robot enabled
                  ↓
           RobotAlertMessage
                  ↓
          StrategyFactory
                  ↓
              Webhook
                  ↓
          Alert Delivery Log
```

---

# 21. robot_id 快照与机器人删除的关系

这是需要明确的历史数据语义。

假设：

```text
Execution A
robot_id snapshot = 1
```

之后：

```text
Robot 1 被删除
```

Execution A 后续发生：

```text
FAILED
```

此时：

```text
robot_id = 1
robot 不存在
```

则：

```text
无法发送
```

但：

```text
Execution 仍然 FAILED
```

Alert Log：

```text
status = failed
error_message = robot_not_found
```

因此：

> robot_id 快照保证历史选择一致性，但不保证机器人资源永久存在。

---

# 22. Robot 配置修改

如果修改：

```text
webhook_url
secret
config_json
```

只影响后续发送。

已经创建的：

```text
cron_task_log.robot_id
```

不改变。

但是发送时读取：

```text
cron_robot
```

得到最新配置。

因此：

```text
robot_id = stable identity
robot configuration = mutable
```

这是合理的。

---

# 23. Robot Test

接口：

```text
POST /api/cron/robots/{id}/test
```

仅超级管理员。

测试不创建：

```text
Execution
cron_task_log
RunOnce
```

直接：

```text
Robot
  ↓
Strategy
  ↓
Webhook
```

测试消息：

```text
🔔 schedule-job 机器人测试

机器人：生产钉钉告警
平台：DingTalk

状态：连接测试成功
时间：2026-09-09 10:00:00
```

测试结果更新：

```text
last_test_at
last_success_at
last_failure_at
last_error
```

---

# 24. API

## 24.1 Robot

```text
GET    /api/cron/robots
POST   /api/cron/robots
PUT    /api/cron/robots/{id}
DELETE /api/cron/robots/{id}

POST   /api/cron/robots/{id}/test
POST   /api/cron/robots/{id}/enable
POST   /api/cron/robots/{id}/disable
```

## 24.2 NodeGroup

```text
POST /api/cron/node-groups/{groupId}/robot
DELETE /api/cron/node-groups/{groupId}/robot
```

或者复用：

```text
PUT /api/cron/node-groups/{groupId}
```

直接修改：

```json
{
    "robot_id": 3
}
```

如果当前项目已有 NodeGroup Update API，优先复用现有接口，避免增加无意义 API。

---

# 25. 敏感信息

以下字段必须保护：

```text
webhook_url
secret
config_json
```

原则：

```text
数据库
    ↓
加密存储

API Response
    ↓
永远不返回完整 Secret/Webhook
```

列表：

```text
https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=******
```

编辑接口也不应该直接返回明文 Secret。

如果项目已经存在统一加密组件：

> 优先复用现有加密能力，不重新实现 AES。

---

# 26. 审计

机器人：

```text
CREATE
UPDATE
DELETE
ENABLE
DISABLE
TEST
```

节点组：

```text
SET_ROBOT
REMOVE_ROBOT
```

全部进入现有操作审计体系。

审计日志中：

```text
secret
webhook_url
```

必须脱敏。

---

# 27. Metrics

建议：

```text
cron_robot_send_total
cron_robot_send_success_total
cron_robot_send_failure_total
cron_robot_send_duration_ms

cron_robot_test_total
cron_robot_test_failure_total
```

低基数标签：

```text
platform
status
```

不要使用：

```text
execution_id
robot_id
group_id
```

作为 Prometheus 高基数标签。

---

# 28. 异常边界

必须保证：

```text
Robot Exception
      ↓
Alert Service
      ↓
记录日志
      ↓
Execution 不受影响
```

禁止：

```text
Robot Exception
      ↓
ExecutionService catch
      ↓
Workflow failure
```

更不能进入：

```text
Saga compensation
```

机器人告警是旁路能力。

---

# 29. 测试方案

## 29.1 权限

```text
超级管理员新增机器人 → 200
超级管理员编辑机器人 → 200
超级管理员测试机器人 → 200
超级管理员设置 NodeGroup robot → 200

普通管理员新增机器人 → 403
普通管理员编辑机器人 → 403
普通管理员测试机器人 → 403
普通管理员设置 NodeGroup robot → 403
```

---

## 29.2 NodeGroup

```text
robot_id = 0
    → 不发送

robot_id = 有效启用机器人
    → 成功

robot_id = 不存在
    → 422

robot_id = 禁用机器人
    → 设置时 422
```

---

## 29.3 Execution Snapshot

必须测试：

```text
T1:
Execution 创建
robot_id = 1

T2:
NodeGroup.robot_id = 2

T3:
Execution FAILED
```

预期：

```text
告警仍然使用 robot_id = 1
```

这是 P0 测试。

---

## 29.4 CAS 并发

两个 Worker 同时：

```text
RUNNING → FAILED
```

预期：

```text
Worker A
CAS success
Event

Worker B
CAS conflict
No Event
```

因此：

```text
Alert Event = 1
```

Delivery Log 可以记录：

```text
1 次发送
```

---

## 29.5 Webhook Timeout

模拟：

```text
Webhook connect timeout
Webhook request timeout
HTTP 500
HTTP 429
HTTP 200 + business error
```

预期：

```text
Alert = failed
Execution 状态不变
```

---

## 29.6 Robot 故障

测试：

```text
Robot disabled
Robot deleted
Webhook invalid
Secret invalid
Network error
```

全部要求：

```text
Execution 不受影响
```

---

# 30. 实施顺序

## P0

### P0-1

数据库：

```text
cron_agent_node_group.robot_id
cron_task_log.robot_id
```

### P0-2

Execution register 时：

```text
NodeGroup.robot_id
    ↓
cron_task_log.robot_id
```

完成 robot_id snapshot。

### P0-3

Terminal CAS：

```text
CAS success
    ↓
ExecutionFinishedEvent
```

严格区分：

```text
Event 唯一性
```

和：

```text
Delivery
```

### P0-4

Alert 不允许阻塞 Execution：

```text
Event
 ↓
Coroutine
 ↓
Alert
```

---

## P1

### P1-1

创建：

```text
cron_robot
cron_robot_alert_log
```

### P1-2

系统设置：

```text
系统设置
└── 机器人警告
```

### P1-3

Robot CRUD：

```text
CronRobotController
CronRobotService
CronRobotRepository
```

### P1-4

Strategy：

```text
RobotStrategyInterface
WeComRobotStrategy
DingTalkRobotStrategy
FeishuRobotStrategy
RobotStrategyFactory
```

### P1-5

DTO：

```text
RobotAlertMessage
RobotSendResult
```

### P1-6

NodeGroup：

```text
setRobot()
removeRobot()
```

### P1-7

Webhook timeout：

```text
connect timeout
request timeout
```

### P1-8

Execution Alert：

```text
ExecutionFinishedEvent
CronAlertListener
CronAlertService
```

### P1-9

权限、审计、Metrics、测试。

---

# 31. 暂不实现

第一版明确不做：

```text
MQ
Kafka
Redis Stream
独立 Alert Worker
告警聚合
告警降噪
告警升级
多机器人同时发送
复杂通知路由
告警恢复通知
```

这些都属于后续演进能力。

---

# 32. 后续演进

如果未来需要可靠投递：

```text
Execution
    ↓
Alert Event
    ↓
MQ
    ↓
Alert Worker
    ↓
cron_robot_alert_log
    ↓
Robot
```

如果需要告警恢复：

```text
FAILED
   ↓
ALERT

后续 SUCCESS
   ↓
RECOVERY ALERT
```

如果需要多机器人：

```text
NodeGroup
   ↓
robot_ids[]
```

但当前需求：

```text
NodeGroup
   ↓
一个 robot_id
```

不提前设计。

---

# 33. 最终架构

```text
                          系统设置
                             │
                       机器人警告
                             │
                             ↓
                      ┌─────────────┐
                      │ cron_robot  │
                      │ Global      │
                      └──────┬──────┘
                             ↑
                             │ robot_id
                             │
                    cron_agent_node_group
                             │
                             ↓
                       Execution 创建
                             │
                             ↓
                  cron_task_log.robot_id
                       （Snapshot）
                             │
                             ↓
                     FAILED / TIMEOUT
                             │
                             ↓
                       Terminal CAS
                             │
                   affected_rows = 1
                             │
                             ↓
                  ExecutionFinishedEvent
                             │
                             ↓
                     CronAlertService
                             │
                             ↓
                    RobotAlertMessage
                             │
                             ↓
                  RobotStrategyFactory
                     ┌───────┼───────┐
                     ↓       ↓       ↓
                    企微     钉钉     飞书
                             │
                             ↓
                    Webhook Timeout
                             │
                             ↓
                  cron_robot_alert_log
                             │
                      ┌──────┴──────┐
                      ↓             ↓
                   SUCCESS        FAILED
```

---

# 34. 最终设计原则

本方案最终遵循：

```text
① Robot 是全局资源
② NodeGroup 只保存 robot_id
③ Execution 创建时 Snapshot robot_id
④ Execution 状态机不依赖 Robot
⑤ Terminal CAS 只负责 Event 唯一性
⑥ Delivery 独立记录
⑦ Alert 不阻塞 Execution
⑧ Webhook 必须有 timeout
⑨ Strategy 隔离平台差异
⑩ RobotAlertMessage 隔离业务数据与平台格式
⑪ 不引入 MQ 等复杂基础设施
⑫ 超级管理员负责机器人配置与节点组关联
```

最终形成：

```text
                    Execution Core
                         │
                         │ Event
                         ↓
                   Alert Sidecar
                         │
                         ↓
                  Robot Strategy
                         │
             ┌───────────┼───────────┐
             ↓           ↓           ↓
            企微         钉钉         飞书
```

**核心状态机保持稳定，机器人告警作为旁路能力接入。**
