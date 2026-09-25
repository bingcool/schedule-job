# schedule-job InterfaceApi 契约布局

与 [InterfaceApi.md](/Users/macbook/Documents/wwwphp/swoolefy/docs/InterfaceApi.md) 一致：契约代码在仓库 `InterfaceApi/`，PSR-4 根命名空间为 `InterfaceApi\`（见 `App/Autoloader.php`）。

## 目录

| 路径 | 命名空间 |
|---|---|
| `InterfaceApi/bin/` | CLI（`reference-check.php`、`generate-client.php`、`generate-openapi.php`） |
| `InterfaceApi/Support/` | `InterfaceApi\Support\`（公共基础设施，见 [`Support/README.md`](../InterfaceApi/Support/README.md)；含 `Generator\`） |
| `InterfaceApi/ScheduleJob/App/Module/Common/` | `InterfaceApi\ScheduleJob\App\Module\Common\` |
| `InterfaceApi/ScheduleJob/App/Module/Cron/` | Request / Response / Dto |
| `InterfaceApi/ScheduleJob/App/Module/Staff/` | Request / Response / Dto |

`{项目}` = `ScheduleJob`，`{应用}` = `App`，与服务端 `App\Module\...` 目录结构对应。

## 部署与 Autoloader

生产/CI 可在 `composer.json` 的 `require` 里固定 `interface-api` 版本，类从 **vendor** 加载（与改 `composer.json` 的 path 无关）。

解析逻辑在 `App/Autoloader.php`（`resolveInterfaceApiDirectory()` + `loadFromFile()`）：

1. **默认（未开本地开关）**：优先由 Composer 已安装的 `interface-api`（vendor）解析；若项目内仍有 `{根}/InterfaceApi/`，App Autoloader 也会在队列末尾尝试该目录。
2. **`REGISTER_LOCAL_INTERFACE_API=1`**（`App/.env` 或进程环境变量）：**只**在同级 `{上一级}/interface-api-service/` 查找 `InterfaceApi\` 类；不使用项目内 `InterfaceApi/`，**也不会**在本地找不到时回退 vendor（会直接抛错，避免误用 composer 里的旧契约）。

本地模式会 `Autoloader::register(true)` **prepend**，保证先于 Composer 执行；关闭本地开关时用 `register(false)`，便于正常使用 vendor 里的契约包。

目录示例（dev + 独立契约仓）：

```text
/home/wwwroot/schedule-job/App/...
/home/wwwroot/interface-api-service/App/...
/home/wwwroot/interface-api-service/ScheduleJob/App/Module/...
```

本地联调独立契约仓：在 `App/.env` 设置 `REGISTER_LOCAL_INTERFACE_API=1`，并把契约仓 clone 到与 schedule-job 同级的 `interface-api-service`。

## 服务端引用

Controller / Service 通过 `InterfaceApi\ScheduleJob\App\Module\...` 引用契约类，不再使用 `App\Module\...\Dto|Request|Response`。

`InterfaceApi\Support` 对 Swoolefy 的薄继承是**面向 Swoolefy 项目的正式设计**（见 [`Support/README.md`](../InterfaceApi/Support/README.md)），嵌仓或独立契约仓均如此，以保证 HttpRoute 校验与 JSON 信封行为与运行时一致。

## §2 引用边界检查

生成器位于 `InterfaceApi/Support/Generator/`（`ReferenceChecker`、`ClientGenerator`、`ClientWriter`）。

```bash
php InterfaceApi/bin/reference-check.php
```

默认扫描 `InterfaceApi/ScheduleJob/`（跳过 `Client/`、`Support/`）。失败时输出 `文件:行号 FQCN`。生成 Client 前也会自动执行同一检查。

## HTTP 契约接口（ApiInterface）

| 模块 | 汇总接口 | 子接口（与 Controller 一一对应） |
|---|---|---|
| Cron | `CronApiInterface`（extends 子接口，Client：`CronApi`） | `CronTaskManagerApiInterface` → `CronTaskManagerApi`，`CronRobotApiInterface` → `CronRobotApi` |
| Staff | `StaffApiInterface`（文档汇总，无方法） | `StaffAuthApiInterface`、`StaffUserApiInterface`、`StaffRoleApiInterface` → 各生成 `StaffAuthApi`、`StaffUserApi`、`StaffRoleApi` |

路径：契约接口 `InterfaceApi/ScheduleJob/App/Module/{Cron|Staff}/Interface/`；生成 Client `…/Module/{Cron|Staff}/Client/`（命名空间 `…\Module\{Cron|Staff}\Client`）。各 `Client/` 含 `README.md`，说明仅脚本生成、禁止 AI/Agent 直接改 `*.php`。路由与 `App/Router/Module/*.php` 对齐，方法上标注 `#[Route]` / 接口上 `#[RouteGroup(prefix: '/api/v1', ...)]`。

Controller 实现对应子接口，例如 `CronTaskManagerController implements CronTaskManagerApiInterface`。

契约接口上：`#[ApiController]` / `#[ApiOperation]` 描述模块与方法（对齐 OpenAPI 文案）；具体 JSON 字段见 Request / Response / DTO 属性上的 `#[ApiProperty]`。

生成 HTTP Client（**一个 `*ApiInterface` 对应一个 `*Api` Client**；无 API 方法的汇总接口如 `StaffApiInterface` 会跳过）：

```bash
php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App
```

生成 OpenAPI 3.0 YAML（扫描 `Interface/*ApiInterface` 的 `#[Route]`，规则同 swoolefy `gen:apidoc`）：

```bash
php InterfaceApi/bin/generate-openapi.php --service=ScheduleJob/App
# 默认输出 InterfaceApi/ScheduleJob/openapi/openapi-{cron|staff}.yaml
# 模块 title/description：InterfaceApi/ScheduleJob/openapi-modules.json
```

`--service` 为 `InterfaceApi/` 下相对路径，须以 `App` 结尾（如 `ScheduleJob/App`）；校验 `App` 目录存在后仅扫描其下 `Module/`。

## 后续

- Controller 与契约方法签名保持一致；变更路由后重跑上述脚本。
