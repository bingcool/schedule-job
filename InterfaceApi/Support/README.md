# InterfaceApi Support

`InterfaceApi\Support` 是契约包的**公共基础设施层**（命名空间 `InterfaceApi\Support\`），为各项目契约（如 `ScheduleJob/App/Module/...`）提供统一能力，与 [swoolefy InterfaceApi 规范](https://github.com/bingcool/swoolefy/blob/master/docs/InterfaceApi.md) 对齐。

## 职责

| 类别 | 说明 |
|------|------|
| **路由与文档注解** | `Route`、`RouteGroup`、`ApiOperation`、`ApiController`、`ApiProperty` 等，供 `*ApiInterface` 与 Request/Response/DTO 使用 |
| **HTTP 契约基类** | `BaseRequest` / `BaseResponse` / `BasePageRequest`、分页与列表 DTO 抽象、特殊响应（Stream/Download/Chunked） |
| **校验与序列化** | `ValidationRule`、`ArrayList`、`CovertProperty` 等，与运行时 HttpRoute 校验、Client 反序列化一致 |
| **调用方 Client 基类** | `BaseClientApi`、`ClientException`、`NacosServiceDiscovery`（生成出的 `*Api` 继承于此） |
| **Generator/** | 引用边界检查（§2）、Client / OpenAPI 生成器及 CLI 引导代码（见 `InterfaceApi/bin/`） |

业务 Request、Response、模块 Interface **不应**放在本目录；它们位于 `InterfaceApi/{项目}/App/Module/...`。

## 与 Swoolefy 的关系

**InterfaceApi 契约包本身就是面向 Swoolefy 技术栈设计的**（HttpRoute、注解体系、JSON 信封、校验链路与 swoolefy 运行时一致）。因此 Support 里对 Swoolefy 的**薄继承是预期设计**，例如：

- `BaseRequest` / `BaseResponse` / `BasePageRequest` → `\Swoolefy\Http\…`
- `ValidationRule` → `\Swoolefy\Annotation\Validation\ValidationRule`

无论契约仓是嵌在业务仓库（如 schedule-job）内，还是与业务仓库**同级独立 checkout**，上述继承方式**均合理且应保持**，不是临时妥协。消费方通过 `composer` 依赖 `bingcool/swoolefy`，并配合 `App\Autoloader` 加载 `InterfaceApi\` 即可。

变更 Support 时仍需保持**对外 API（类名、注解语义、与 swoolefy 的继承关系）**稳定，并遵循 `InterfaceApi.md` 约定。

## 变更约束

- **请勿随意修改**本目录下的 PHP 实现。Support 是跨项目、跨仓库的契约运行时与工具链基础；改动会影响引用检查、Client/OpenAPI 生成及所有消费方编译/运行。
- 确需调整时：先对照 swoolefy `InterfaceApi.md` 与 `Autoloader` 约定，在评审中说明兼容性；避免 AI / Agent 在无明确需求下批量「重构」或「优化」本目录。
- 生成物（各模块 `Client/*.php`、OpenAPI YAML）**不得**在本目录维护；请改契约接口与 `InterfaceApi/bin/generate-*.php` 流程。

## 相关命令

```bash
# §2 引用边界
php scripts/interface_api_reference_check.php

# HTTP Client / OpenAPI
php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App
php InterfaceApi/bin/generate-openapi.php --service=ScheduleJob/App
```

更完整的目录说明见仓库 `docs/interface-api-layout.md`。
