# AGENTS.md

本文件为 AI 编码助手（Agent）提供在本仓库工作的约定与操作指引。开始编码前请先读完本文件，并优先遵循此处规范。

## 项目概述

**Schedule Job** 是基于 [Swoolefy](https://github.com/bingcool/swoolefy)（构建于 Swoole 扩展之上的常驻内存框架）的分布式 Cron 任务调度平台，由两个**独立部署、通过 MySQL 协作**的子系统组成：

| 子系统 | 入口 | 模式 | 默认端口 | 职责 |
|--------|------|------|----------|------|
| 管理端 admin-manager | `cli.php` | HTTP Server | `9502` | Web 控制台、REST API、RBAC、任务 CRUD、审计 |
| 执行端 cron agent | `cron.php` | Worker Service（常驻进程） | `9506` | 轮询拉任务、本机 fork/HTTP/提交 K8s Job、回写日志与心跳 |

协作模型：**管理端只写配置 → Agent 只读配置、执行并回写结果**。二者不主动推送，Agent 通过定时轮询 DB 感知变更（Runtime Diff）。

- 语言/运行时：PHP `>= 8.4` + Swoole 扩展（`>= 6.1`，可用 swoole-cli 替代原生 php）
- 数据库：MySQL `5.7+ / 8.x`
- 依赖：`bingcool/swoolefy ~6.3.10`、`bingcool/library ^6.0.4`、`phpmailer/phpmailer ~7.1.0`
- 鉴权：JWT（HS256）；前端为内置静态 SPA（Vue 2），位于 `App/Module/Cron/static/cron-admin`

## 常用命令

```bash
composer install                 # 安装依赖（vendor 目录，勿提交/勿手改 vendor）

# 管理端
php cli.php start App            # 前台启动
php cli.php start App --daemon=1 # 守护进程
php cli.php stop|restart|reload|status App

# Agent 执行端
php cron.php start|stop|restart|status App

# 脚本/生成任务
php script.php start App -c <command>   # 见 App/Scripts/Kernel.php

# 接口契约检查与生成
php InterfaceApi/bin/reference-check.php
php InterfaceApi/bin/generate-client.php  --service=ScheduleJob/App
php InterfaceApi/bin/generate-openapi.php --service=ScheduleJob/App
```

- 应用名固定为 `App`（区分大小写），须与入口文件中 `APP_META_ARR` 的 key 一致。
- 数据库初始化：导入 `migrations/cron.sql`，预置超管账号 `admin` / `123456789`（生产须立即改密）。

## 目录与分层约定

服务端业务代码在 `App/Module/{Cron,Staff}`，采用类 DDD 分层，新增功能请严格沿用同一分层与命名：

```
App/
├── Config/              # 配置（app.php 组件池、auth.php、database.php、component/*.php 等）
├── Protocol/conf.php    # 协议/服务器级配置（Swoole setting、bootstrap、event_handler、response_formatter）
├── Router/              # 声明式路由：api.php 公共；Module/CronManager.php、Module/StaffManager.php 模块
├── Controller/          # 顶层示例控制器；业务控制器放 Module/*/Controller
├── Module/<Domain>/
│   ├── Controller/      # 入口，实现 InterfaceApi 契约子接口，做参数校验与调度 Service
│   ├── Service/         # 业务逻辑（如 ExecutionService 是执行生命周期唯一入口）
│   ├── Repository/      # 数据访问（基于 Mapper）
│   ├── Mapper/          # SQL/表映射
│   ├── Entity/          # 领域实体
│   ├── Assembler/       # 实体 ↔ 数组/DTO 组装
│   ├── Exception/       # 领域异常
│   └── static/          # 该模块前端静态资源（Cron）
├── WorkerCron/          # Agent 端进程：MainCronProcess + Schedule{Fork,Url,K8s}CronProcess + conf/*.php
├── Process/             # 管理端内置定时进程（PurgeExpiredTaskLogs、ScanNodeLivenessAlerts）
├── Event.php            # 生命周期钩子（onInit 注册进程、onWorkerStart 预加载）
├── Bootstrap.php        # 全局请求钩子（安全头、多语言）
├── Factory.php          # 容器取组件（getDb / getGuard）
└── Autoloader.php       # 命名空间与 InterfaceApi 解析
```

- 取 DB / 鉴权等组件统一走 `App\Factory`（`Factory::getDb()`、`Factory::getGuard()`），不要 new 底层连接。
- 路由用 `Swoolefy\Http\Route`：`Route::group(['prefix'=>..., 'middleware'=>[...]], fn)`，handler 写 `dispatch_route => [Controller::class, 'method']`。
- 中间件链示例：受保护 API 走 `CorsMiddleware → AuthenticateMiddleware → MenuPagePermissionMiddleware`；Agent 专用接口（`/agent/tasks`、`/agent/heartbeat`、`/agent/report`）只用 CORS + api_key 鉴权，**不要挂 JWT 鉴权**。健康检查 `/health`、`/ready` 必须保持公开。

## 接口契约（InterfaceApi）——重要约束

- 契约代码在仓库 `InterfaceApi/`，PSR-4 根命名空间 `InterfaceApi\`。Controller/Service 引用 Request/Response/Dto 一律用 `InterfaceApi\ScheduleJob\App\Module\...`，**不要**再用 `App\Module\...\Dto|Request|Response`。
- `InterfaceApi/**/Client/*.php` 与 `openapi/*.yaml` 为**脚本生成产物，禁止 AI/Agent 直接编辑**。需要变更时改契约接口/DTO 注解后重跑 `generate-client.php` / `generate-openapi.php`。
- 契约与实现对齐：Controller `implements` 对应 `*ApiInterface`，方法签名保持一致；改动路由后重跑 `reference-check.php` 与生成脚本。
- 详见 `docs/interface-api-layout.md`。

## Agent 执行端可靠性要点

修改 Agent/执行相关代码前须知：

- `ExecutionService` 是执行状态机的唯一入口（upsert + CAS，按 `exec_batch_id` 去重），不要把 `status` 的 UPDATE 散落各处。
- **防重复执行**依赖 DB 约束：调度抢槽 `schedule_slot_claim`（同 `cron_id + 调度点` 唯一赢家）、RunOnce 的 `UNIQUE(request_id)`（at-most-once）。改动这些逻辑务必保持唯一约束语义。
- **执行租约（Lease）**：`bootAgent()` 生成 boot_id、回收过期 RUNNING；续租间隔由代码计算 `(EXECUTION_LEASE_DURATION/2)-5`，不单独配置。`EXECUTION_LEASE_DURATION` 显式设置时不得 < 20，否则启动抛异常。
- 任务进程配置在 `App/WorkerCron/conf/schedule_{fork,url,k8s}_conf.php`，通过 `args` 回调（`task_list`/`node_heartbeat_ack`/`run_once_*`/`schedule_slot_claim`）与框架调度器解耦。

## 编码风格

- 遵循现有约定：文件顶部 `declare(strict_types=1);`（新模块普遍使用），类/方法保持与同层文件一致的命名与结构。
- 优先使用 PHP 8 特性（构造器属性提升、`match`、枚举如 `CronTaskOperationType`/`RobotPlatform`），与既有代码保持风格统一。
- 注释仅在逻辑复杂处补充，中文注释与项目现状一致；不要给简单代码堆注释。
- 日志涉及敏感信息受 `ENABLE_LOG_SANITIZE` 控制；Webhook 明文落库、API 返回需脱敏，写操作仅限超级管理员。

## 配置与环境

- 主配置 `App/.env`（参考 `App/.env.example`）；生产/CI 通过环境变量注入，多环境映射 dev/test/gra/prd。
- 关键变量：`DB_*`、`AUTH_JWT_*`（生产必改 secret）、`CRON_NODE_ID`/`CRON_NODE_API_KEY`、`CRON_POLL_INTERVAL`(20)、`CRON_HEARTBEAT_INTERVAL`(15)、`CRON_TASK_LOG_DELETE_DAY`(7)、`EXECUTION_LEASE_*`、`WEBHOOK_HOST_*`。
- 本地联调独立契约仓：设 `REGISTER_LOCAL_INTERFACE_API=1` 并将契约仓置于同级 `interface-api-service`。

## 提交与验证清单

改动后请自测：

1. `composer` 依赖可解析，入口能 `start` 成功（Admin 或 Agent 视改动而定）。
2. 若动到接口/DTO/路由：跑 `reference-check.php` + 重新生成 client/openapi，确认无漂移。
3. 若动到执行/调度/租约：确认唯一约束与状态机语义未被破坏，不会导致重复执行或日志被覆盖。
4. 不要提交 `vendor/`、`App/.env`（真实密钥）、运行时产物（`/tmp/schedule-job/`、`App/Storage/Logs`）。

## 参考文档（`docs/`）

- `schedule-job-P0-Reliability-Technical-Plan.md`、`schedule-job-P0-bug.md`：可靠性方案与已知问题
- `schedule-job-k8s-deployment.md`、`deploy/kubernetes/`：K8s 任务与 RBAC
- `schedule-job-防多Agent重复执行.md`、`schedule-job-shell-危险命令过滤方案.md`（对应 `ShellCommandGuard`）
- `robot-alert.md`：机器人告警；`interface-api-layout.md`：契约布局
- `schedule-job-repository-layer.md`：数据访问层设计
