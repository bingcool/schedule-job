# ScheduleJob 契约包

`InterfaceApi/ScheduleJob/` 是 **schedule-job** 项目在 InterfaceApi 契约仓库中的根目录。PSR-4 下对应命名空间前缀 `InterfaceApi\ScheduleJob\`（例如 `InterfaceApi\ScheduleJob\App\Module\Cron\...`）。

与业务服务端 `App/Module/...` 目录结构一一对应，但类型定义在契约侧：`Interface/`、`Request/`、`Response/`、`Dto/` 等。

## 目录概览

| 路径 | 说明 |
|------|------|
| `App/Module/Cron/`、`App/Module/Staff/` | 各模块契约（接口、入参/出参、Dto） |
| `App/Module/*/Interface/` | `*ApiInterface`，带 `#[Route]` / `#[RouteGroup]`，供生成 Client 与 OpenAPI |
| `App/Module/*/Client/` | 脚本生成的 HTTP Client（勿手改） |
| `openapi/` | 脚本生成的 OpenAPI YAML |
| `openapi-modules.json` | OpenAPI 各模块 `info.title` / `description` |
| **`interface-api.json`** | **Client 生成用服务发现名（见下）** |

更完整的仓库布局见项目根 [`docs/interface-api-layout.md`](../../docs/interface-api-layout.md)。

## `interface-api.json` 做什么

该文件放在 **`ScheduleJob/` 目录下**（与 `App/` 同级），供 `generate-client.php` 读取。  
CLI 参数 `--service=ScheduleJob/App` 中的 `ScheduleJob/App` 会映射到磁盘路径 `ScheduleJob/App/`，其**上一级** `ScheduleJob/` 即为「包根」，`interface-api.json` 必须位于该包根。

### 格式

键名必须与 `--service` 传入的路径一致（使用 `/`，例如 `ScheduleJob/App`）。值为对象，至少包含非空字符串 **`serviceName`**：

```json
{
  "ScheduleJob/App": {
    "serviceName": "schedule-job"
  }
}
```

- **`serviceName`**：写入各模块 `Client/*Api.php` 中的 `protected string $serviceName`。  
  Client 通过 Nacos 等服务发现按该名称解析 **schedule-job** 后端实例（见 `InterfaceApi\Support\NacosServiceDiscovery`）。  
  应与 Nacos / 部署里注册的服务名一致，否则生成的 Client 无法正确寻址。

### 何时会用到

- **会读**：`php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App`  
  缺少文件、JSON 无效、或缺少当前 `serviceKey` 对应的 `serviceName` 时，生成器会直接报错并停止（参见 InterfaceApi 方案 §5.4）。
- **不读**：`reference-check.php`、`generate-openapi.php` 不依赖本文件（OpenAPI 使用 `openapi-modules.json` 等）。

修改 `serviceName` 或新增 `--service` 指向的另一个 `{项目}/App` 包后，需重新执行 `generate-client.php`，Client 中的 `$serviceName` 才会更新。

## 常用命令

在 schedule-job 仓库根目录：

```bash
# §2 引用边界（默认扫描 InterfaceApi/ScheduleJob）
php InterfaceApi/bin/reference-check.php

# 生成 Client（依赖 interface-api.json）
php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App

# 生成 OpenAPI
php InterfaceApi/bin/generate-openapi.php --service=ScheduleJob/App
```

契约变更流程：先改 `Interface/` 与 Request/Response/Dto → `reference-check.php` → 再生成 Client / OpenAPI。
