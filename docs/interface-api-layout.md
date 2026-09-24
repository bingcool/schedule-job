# schedule-job InterfaceApi 契约布局

与 [InterfaceApi.md](/Users/macbook/Documents/wwwphp/swoolefy/docs/InterfaceApi.md) 一致：契约代码在仓库 `InterfaceApi/`，PSR-4 根命名空间为 `InterfaceApi\`（见 `App/Autoloader.php`）。

## 目录

| 路径 | 命名空间 |
|---|---|
| `InterfaceApi/bin/` | CLI（如 `generate-client.php`） |
| `InterfaceApi/Support/` | `InterfaceApi\Support\`（注解、BaseRequest/Response、Client 基类、`Generator\` 引用检查与 Client 生成） |
| `InterfaceApi/ScheduleJob/App/Module/Common/` | `InterfaceApi\ScheduleJob\App\Module\Common\` |
| `InterfaceApi/ScheduleJob/App/Module/Cron/` | Request / Response / Dto |
| `InterfaceApi/ScheduleJob/App/Module/Staff/` | Request / Response / Dto |

`{项目}` = `ScheduleJob`，`{应用}` = `App`，与服务端 `App\Module\...` 目录结构对应。

## 服务端引用

Controller / Service 通过 `InterfaceApi\ScheduleJob\App\Module\...` 引用契约类，不再使用 `App\Module\...\Dto|Request|Response`。

`InterfaceApi\Support` 在 schedule-job 内对 Swoolefy 做了薄继承（如 `BaseRequest`、`BaseResponse`、`ValidationRule`），保证 HttpRoute 校验与 JSON 信封行为不变；独立发包时可改回纯 Support 实现。

## §2 引用边界检查

生成器位于 `InterfaceApi/Support/Generator/`（`ReferenceChecker`、`ClientGenerator`、`ClientWriter`）。

```bash
php scripts/interface_api_reference_check.php
```

默认扫描 `InterfaceApi/ScheduleJob/`（跳过 `Client/`、`Support/`）。失败时输出 `文件:行号 FQCN`。

## 重新迁移契约文件

契约以 `InterfaceApi/ScheduleJob/App/Module/` 为唯一来源；`App/Module` 下不再保留 Dto/Request/Response 副本。若需从历史 App 树重新拷贝，可执行：

```bash
python3 scripts/migrate_interface_api_contracts.py
```

然后全局替换引用为 `InterfaceApi\ScheduleJob\App\Module\...`（脚本已修复 `\App\Module\` 误替换问题）。

## HTTP 契约接口（ApiInterface）

| 模块 | 汇总接口 | 子接口（与 Controller 一一对应） |
|---|---|---|
| Cron | `CronApiInterface`（extends 子接口，Client：`CronApi`） | `CronTaskManagerApiInterface` → `CronTaskManagerApi`，`CronRobotApiInterface` → `CronRobotApi` |
| Staff | `StaffApiInterface`（文档汇总，无方法） | `StaffAuthApiInterface`、`StaffUserApiInterface`、`StaffRoleApiInterface` → 各生成 `StaffAuthApi`、`StaffUserApi`、`StaffRoleApi` |

路径：契约接口 `InterfaceApi/ScheduleJob/App/Module/{Cron|Staff}/Interface/`；生成 Client `…/Module/{Cron|Staff}/Client/`（命名空间 `…\Module\{Cron|Staff}\Client`）。各 `Client/` 含 `README.md`，说明仅脚本生成、禁止 AI/Agent 直接改 `*.php`。路由与 `App/Router/Module/*.php` 对齐，方法上标注 `#[Route]` / 接口上 `#[RouteGroup(prefix: '/api/v1', ...)]`。

Controller 实现对应子接口，例如 `CronTaskManagerController implements CronTaskManagerApiInterface`。

契约接口上：`#[ApiController]` / `#[ApiOperation]` 描述模块与方法（对齐 OpenAPI 文案）；具体 JSON 字段见 Request / Response / DTO 属性上的 `#[ApiProperty]`。

从路由与 Controller 重新生成子接口：

```bash
python3 scripts/generate_api_interfaces.py
```

生成 HTTP Client（**一个 `*ApiInterface` 对应一个 `*Api` Client**；无 API 方法的汇总接口如 `StaffApiInterface` 会跳过）：

```bash
php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App
```

`--service` 为 `InterfaceApi/` 下相对路径，须以 `App` 结尾（如 `ScheduleJob/App`）；校验 `App` 目录存在后仅扫描其下 `Module/`。

## 后续

- Controller 与契约方法签名保持一致；变更路由后重跑上述脚本。
