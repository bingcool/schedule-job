# schedule-job InterfaceApi 契约布局

与 [InterfaceApi.md](/Users/macbook/Documents/wwwphp/swoolefy/docs/InterfaceApi.md) 一致：契约代码在仓库 `InterfaceApi/`，PSR-4 根命名空间为 `InterfaceApi\`（见 `App/Autoloader.php`）。

## 目录

| 路径 | 命名空间 |
|---|---|
| `InterfaceApi/Support/` | `InterfaceApi\Support\`（注解、BaseRequest/Response、Client 基类） |
| `InterfaceApi/ScheduleJob/App/Module/Common/` | `InterfaceApi\ScheduleJob\App\Module\Common\` |
| `InterfaceApi/ScheduleJob/App/Module/Cron/` | Request / Response / Dto |
| `InterfaceApi/ScheduleJob/App/Module/Staff/` | Request / Response / Dto |

`{项目}` = `ScheduleJob`，`{应用}` = `App`，与服务端 `App\Module\...` 目录结构对应。

## 服务端引用

Controller / Service 通过 `InterfaceApi\ScheduleJob\App\Module\...` 引用契约类，不再使用 `App\Module\...\Dto|Request|Response`。

`InterfaceApi\Support` 在 schedule-job 内对 Swoolefy 做了薄继承（如 `BaseRequest`、`BaseResponse`、`ValidationRule`），保证 HttpRoute 校验与 JSON 信封行为不变；独立发包时可改回纯 Support 实现。

## §2 引用边界检查

swoolefy 仓库尚未提供 `bin/generate-client.php` 时，可在 schedule-job 根目录执行：

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

## 后续

- 按 InterfaceApi.md 定义 `*ApiInterface` + `#[RouteGroup]` / `#[Route]`，Controller `implements` 接口。
- 在 InterfaceApi 包根执行 Client 生成前，需清理契约中对 `App\Module\Entity` 等非 InterfaceApi 引用（或下沉到 Service 组装）。
