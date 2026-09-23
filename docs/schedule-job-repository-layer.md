# Cron / Staff Repository 层技术方案

> 目标：把 `App/Module/Cron` 与 `App/Module/Staff` 的全部 MySQL 访问从 Service 迁到 Repository。  
> 本文只定方案，不改业务语义（软删、Lease CAS、RunOnce UNIQUE、权限范围过滤保持不变）。

---

## 1. 为什么要这一层

现状是 Active Record + Service：

- Controller 不直接打库（已满足）
- Service 里大量 `Entity::query()` / `find()` / `select()` / `insert` / `update`
- 单条有的走 `loadById()`，有的走 `query()->find()` 再 `toArray()`
- 列表经常直接返回 `list<array>` 或 `array<string, mixed>`

问题：

1. Service 同时承担业务规则和 SQL，难测、难复用
2. Cron 直接查 `StaffUserEntity`，跨模块没有边界
3. 同一张表的查询散落在多个 Service（`cron_task_log` 同时在 `ExecutionService`、`CronTaskManagerService`、`CronAlertService`）

Repository 只做「按表存取」。业务判断、事务编排、跨表组合留在 Service。

---

## 2. 分层

```text
Controller       一个 Controller ↔ Dto/{短名}/；共享 ↔ Dto/Common/
    ↓ Request / Response
Service          业务规则、事务、跨 Repository 组合、抛业务异常
    ↓ Entity | Dto | array<int, XxxDto>
Repository       唯一允许 Entity::query() / save() / delete() 的地方
    ↓
Entity           表映射；单条加载只用 loadById / loadByXxx
```

约束：

| 层 | 允许 | 禁止 |
|---|---|---|
| Controller | 调 Service，组 Response | `Entity::query`、`new XxxEntity` 查库 |
| Service | 调本模块 Repository；跨模块只调对方 Repository / Service | `Entity::query`、`->find()`、`->select()`、`->insert()` |
| Repository | `Entity::query()`、`(new Entity())->loadById()`、`$entity->save()` / `delete()` | 调其他模块 Entity；抛「业务文案」异常（找不到由 Service 决定） |
| Entity | `loadById` / `loadByXxx`、字段、casts、SoftDelete | 列表 / 聚合 / join SQL |

`KubernetesCrashRecovery` 里对 `cron_task` 的 `find()` 一并迁到 `CronTaskRepository`。

---

## 3. 返回值约定（核心）

### 3.1 单条记录 → Entity

「按主键或唯一键取出一行」必须走 Entity 加载器，返回实体，禁止 `toArray()`：

```php
public function findById(int $id): ?CronTaskEntity
{
    if ($id <= 0) {
        return null;
    }

    return (new CronTaskEntity())->loadById($id);
}

public function findByAccount(string $account): ?StaffUserEntity
{
    return (new StaffUserEntity())->loadByAccount($account);
}
```

规则：

- 主键：一律 `loadById`，没有的 Entity 先补方法
- 唯一键：补 `loadByXxx`（如 `loadByCode`、`loadByAccount`、`loadByLoginIdentity`）
- Repository 的 `find*` 返回 `?Entity`；Service 的 `require*` 在 `null` 时抛业务异常
- 单条写入后若还要给上层用，返回**已 save 的 Entity**，不要返回 attributes 数组

包含软删行的单条（机器人告警、心跳发现已删节点）用显式方法，例如 `findByIdIncludingDeleted()`，内部 `withoutTrashed()`，仍然返回 `?Entity`。

### 3.1.1 多行整行查询 → `list<Entity>`

对「按条件或 id 集合取出多行、且 SELECT 覆盖 Entity 映射字段（或业务需要的整行子集）」的列表，Repository **返回实体列表**，禁止 `select()->toArray()` 再向上抛 `list<array>` / `array<string, mixed>`。

实现统一走 Trait：

```text
App/Module/Common/Repository/Concerns/HydratesEntityRows.php
    protected function selectRowsToEntities(iterable $rows, string $entityClass): array
```

约定（与 `CronRobotRepository::listAllRows()` 一致）：

| 项 | 规则 |
|---|---|
| 返回类型 | PHPDoc 写 `list<CronTaskEntity>` 等；顺序与 SQL 一致，**不做** `id => Entity` 索引 |
| 方法命名 | 仍用 `listRowsByQuery` / `listRowsByIds` / `listAllRows` / `listAdminRows` 等；语义是「行列表」，只是元素类型为 Entity |
| hydrate | `Entity::query()->…->select()` 的结果交给 `selectRowsToEntities($rows, XxxEntity::class)` |
| Service 边界 | Service / Controller **不**长期持有 Entity 列表对外输出；在组装 Dto、Worker 载荷、OpenAPI 前用 `$entity->getAttributes()`，或 `XxxRowDto::fromEntityRow($entity->getAttributes())` / `fromEntity($entity)` |
| 批量索引 | 若 Service 需要按 id 查找，自行 `foreach` 建 `array<int, Entity>`，Repository 不返回 map |

示例：

```php
/** @return list<CronRobotEntity> */
public function listAllRows(): array
{
    return $this->selectRowsToEntities(
        CronRobotEntity::query()->order('id', 'desc')->select(),
        CronRobotEntity::class,
    );
}
```

```php
// Service：列表进 Dto / 数组协议
$list = array_map(
    static fn (CronTaskEntity $task): array => $task->getAttributes(),
    $this->taskRepository->listRowsByListQuery($query, $scopedNodeIds),
);
foreach ($list as $row) {
    $pageResult->addListItem(CronTaskRowDto::fromEntityRow($row));
}
```

**不属于** `list<Entity>`、仍按 §3.3 / 下方 §3.2 处理的查询：

- 只 SELECT 部分列的投影、聚合、`GROUP BY`、趋势桶（返回标量、id 列表或专用 Dto）
- 单条但走 `query()->find()` 且需数组快照的 CAS / 日志行（如 `findRowByIdForUpdate` 返回 `?array`，仅限 Repository 内部或 Execution 路径）
- 纯主键列表、`id => ids[]` 映射

跨表富化（组名、创建人、任务数）仍在 **Service** 组合多个 Repository 的 Entity / 计数方法；Repository 列表方法只负责本表 Entity 列表。

### 3.2 非单条 → Dto / `array<int, XxxDto>`（Service / 对外边界）

分页列表项、Dashboard 桶、OpenAPI 行对象等在 **Service 层** 组装为 Dto；Repository 层整行列表用 §3.1.1 的 `list<Entity>`，**禁止** Repository 直接返回 `list<array>`。

面向 Controller / Response 的列表、分页、聚合、join 富化产出类型：

```php
/** @return array<int, CronTaskRowDto> */
public function listByQuery(ListTasksQueryDto $query, array $scopedNodeIds): array;

/** @return array<int, ExecutionTrendBucketDto> */
public function listTrendBuckets(ExecutionTrendQueryDto $query): array;
```

分页拆成「列表 + 总数」，Service 再组现有 `*PageResult`：

```php
$total = $this->taskRepo->countByQuery($query, $scope);
$list  = $this->taskRepo->listByQuery($query, $scope);
return (new ListTasksPageResult())->setTotal($total)->setList($list)->setPage(...);
```

`*PageResult` 仍放在 Response 层（给 Controller / OpenAPI 用）。Repository 不返回 Response。

**Service 层**：对 Controller / 其他 Service 暴露的「业务结果」优先返回 **Dto**（如 `CronTaskRowDto`、`StaffUserRowDto`、`AuthMeProfileDto`），禁止再返回 `array<string,mixed>` 裸行。纯 id 列表、`list<int>`、权限 URI 等 §3.3 例外仍可用标量/数组。Response 构造函数可接受 `XxxRowDto|array`（array 仅兼容过渡），序列化统一 `toDeepArray()`。

### 3.2.1 Dto 目录约定（与 Controller 对齐）

**一个 HTTP Controller 对应一个 Dto 子目录**；本模块内多控制器复用的类型放在 `Common`。

```text
App/Module/{Module}/
    Controller/
        CronTaskManagerController.php
        CronRobotController.php
        StaffUserController.php
        ...
    Dto/
        CronTaskManager/          # 仅 CronTaskManagerController 使用的入参 / 出参 / 查询 Dto
        CronRobot/
        StaffUser/
        StaffRole/
        StaffAuth/
        Common/                     # 跨控制器、或 Repository / Service 共享的行对象 / Brief / Option
            StaffUserBriefDto.php
            CronAgentNodeGroupBriefDto.php
            ...
    Repository/
    Response/
```

规则：

| 场景 | 放置目录 | 示例 |
|---|---|---|
| 某 Controller 专属 Request 体、Query、Ack、详情 Dto | `Dto/{Controller 短名}/` | `Dto/CronTaskManager/ListTasksQueryDto.php` |
| 某 Controller 专属 Row（列表行与 OpenAPI 一致且只该接口族用） | 同上 | `Dto/CronRobot/CronRobotRowDto.php` |
| 多个 Controller 或 Service + Repository 共用 | `Dto/Common/` | `StaffRoleBriefDto`、`NodeIdDto`（若 Task / Node 多入口共用） |
| Repository 列表/富化产出、被多个 Service 组装 | 优先 `Dto/Common/`；仅单 Controller 消费可放该 Controller 目录 | `StaffUserBriefDto` |
| 分页壳 | 仍在 `Response/`（不是 Dto 目录） | `ListTasksPageResult` |

命名：`{Controller 短名}` = 类名去掉 `Controller` 后缀（`StaffRoleController` → `Dto/StaffRole/`）。

例外：

- 不提供 JSON API 的控制器（如 `CronAdminController` 静态页）**不**单独建 Dto 目录。
- Worker / Agent 内部协议 Dto 若只被 `CronTaskService` 等 Service 使用、且不对 Admin Controller，可放在 `Dto/Common/` 或后续 `Dto/CronAgent/`（与 Agent 路由控制器对齐时再拆）。

**与 Repository 方案的关系**：Repository 整行列表返回 `list<Entity>`（§3.1.1）；`XxxRowDto` / `XxxBriefDto` 在 Service 由 Entity 映射产出，并遵守 §3.2 类型约定。**文件位置**按上表选 Controller 目录或 `Common`，不要在模块根下堆平铺 Dto。

存量：`Staff` 已拆为 `Dto/StaffAuth/`、`Dto/StaffUser/`、`Dto/StaffRole/`（`Dto/StaffManager/` 已移除）。`Cron` 任务载荷等跨 Service 类型在 `Dto/Common/`（如 `CronTaskPayloadDto`）。新 Dto 仍按控制器目录或 `Common` 落盘，勿再使用 `StaffManager` 命名。

聚合用专用 Dto（已有则复用）：

- `DashboardOverviewDto`、`RuntimeOverviewDto`、`CronTaskStatsResultDto`
- 角色统计、节点心跳计数等补小型 Dto，不要裸数组

### 3.3 标量与 ID 集合（明确例外）

以下**不是记录**，保持标量，避免为每个 id 包一层空 Dto：

| 类型 | 允许返回 |
|---|---|
| 影响行数 / 软删 id | `int` / `bool` |
| 存在性 | `bool` |
| 纯主键列表 | `array<int, int>` |
| 分组主键映射 | `array<int, list<int>>`（如 `cronId => requestIds`） |

一旦元素带名称、状态等字段，必须升级为 `array<int, XxxDto>`。

### 3.4 写入

| 操作 | Repository 做法 | 返回 |
|---|---|---|
| 插入 | `new Entity` + `setData` + `save` | `Entity` |
| 更新已加载实体 | 改字段 + `save` | `Entity` |
| 条件批量更新 | `query()->where(...)->update()` | `int`（affected） |
| CAS（Lease / status） | `query()->where(快照)->update()` | `bool` 或已有 Result Dto |
| 软删 | `findById` 后 `$entity->delete()` | `Entity` 或 `int` id |
| 硬删 / 清关联 | `query()->delete()` | `int` |
| 关联表整表替换 | `delete by fk` + 循环 `save` | `void` |

Service 禁止自己 `new Entity()->save()`。

---

## 4. Entity 补齐

### 4.1 补 `loadById`

| Entity | 现状 |
|---|---|
| `CronTaskRunRequestEntity` | 无 |
| `CronTaskOperationLogEntity` | 无 |
| `CronScheduledTaskRecordEntity` | 无 |
| `CronRobotAlertLogEntity` | 无 |
| `StaffUserRoleEntity` | 无 |
| `StaffUserRelateNodeGroupEntity` | 无 |
| `StaffRolePageEntity` | 无 |
| `StaffRolePermissionEntity` | 无 |

统一签名：

```php
public function loadById(int $id): ?static
{
    return $id <= 0 ? null : $this->loadOne(['id' => $id]);
}
```

### 4.2 已有单条加载器（保留，Repository 只包一层）

| Entity | 方法 |
|---|---|
| `CronTaskEntity` / Log / Node / Group / Robot | `loadById` |
| `StaffUserEntity` | `loadById`、`loadByAccount`、`loadByEmail`、`loadByLoginIdentity` |
| `StaffRoleEntity` | `loadById`、`loadByCode` |
| `StaffMenuPageEntity` | `loadById`；列表继续用 `queryVisible()`，但只在 Menu Repository 内调用 |

`StaffUserEntity::findIdUsingEmail()` 迁到 `StaffUserRepository`，Entity 上删掉静态查询。

### 4.3 SoftDelete

`query()` 继续自动加删除列 `IS NULL`。Repository 需要扫到已删行时，方法名必须带 `IncludingDeleted`，并写清调用方。

Staff 删除列不统一（`delete_at` vs `deleted_at`）本次**不改表**，只在各 Entity / Repository 按现列处理。

---

## 5. Repository 清单

一个表一个 Repository。目录：

```text
App/Module/Cron/Repository/
App/Module/Staff/Repository/
```

命名：`{Entity 去掉 Entity}Repository`。

### 5.1 Cron

| Repository | 表 | 从哪些类迁出 |
|---|---|---|
| `CronTaskRepository` | `cron_task` | `CronTaskManagerService`、`CronTaskService`、`CronScheduledTaskRecordService`、`KubernetesCrashRecovery` |
| `CronTaskLogRepository` | `cron_task_log` | `ExecutionService`、`CronTaskManagerService`、`CronAlertService` |
| `CronAgentNodeRepository` | `cron_agent_node` | `CronTaskManagerService`、`CronTaskService`、`CronAlertService` |
| `CronAgentNodeGroupRepository` | `cron_agent_node_group` | `CronTaskManagerService`、`CronAlertService`、`StaffUserService::assertNodeGroupsExist` |
| `CronRobotRepository` | `cron_robot` | `CronRobotService`、`CronTaskManagerService`、`CronAlertService` |
| `CronTaskRunRequestRepository` | `cron_task_run_request` | `CronTaskManagerService` |
| `CronTaskOperationLogRepository` | `cron_task_operation_log` | `CronTaskManagerService` |
| `CronScheduledTaskRecordRepository` | `cron_scheduled_task_record` | `CronScheduledTaskRecordService`、purge |
| `CronRobotAlertLogRepository` | `cron_robot_alert_log` | `CronAlertService` |

### 5.2 Staff

| Repository | 表 | 从哪些类迁出 |
|---|---|---|
| `StaffUserRepository` | `staff_user` | `StaffUserService`、`StaffAuthService`；**Cron 侧改走本类** |
| `StaffRoleRepository` | `staff_roles` | `StaffRoleService` |
| `StaffMenuPageRepository` | `staff_menu_pages` | `StaffRoleService`、`StaffMenuPermissionService` |
| `StaffUserRoleRepository` | `staff_user_role` | `StaffUserService`、`StaffRoleService` |
| `StaffUserRelateNodeGroupRepository` | `staff_user_relate_node_group` | `StaffUserService` |
| `StaffRolePageRepository` | `staff_role_page` | `StaffRoleService`、`StaffMenuPermissionService` |
| `StaffRolePermissionRepository` | `staff_role_permission` | `StaffRoleService` |

### 5.3 跨模块

Cron 需要用户展示名 / 操作者快照时：

```text
CronTaskManagerService
    → StaffUserRepository::findById() / listByIds()
```

禁止 Cron Service 再 `StaffUserEntity::query()`。

Staff 校验节点组存在时：

```text
StaffUserService
    → CronAgentNodeGroupRepository::listByIds()
```

禁止 Staff Service 再 `CronAgentNodeGroupEntity::query()`。

跨模块只依赖对方 Repository（或已有 Service），不依赖对方 Entity 查询。

---

## 6. 方法命名

| 语义 | 方法名 | 返回 |
|---|---|---|
| 主键单条 | `findById` | `?Entity` |
| 唯一键单条 | `findByAccount` / `findByCode` / `findByBatch` | `?Entity` |
| 含已删单条 | `findByIdIncludingDeleted` | `?Entity` |
| 条件是否存在 | `existsByName` / `existsAccount` | `bool` |
| 计数 | `countByQuery` / `countActiveInGroup` | `int` |
| 整行记录列表 | `listRowsByQuery` / `listRowsByIds` / `listAllRows` / `listVisibleRows` | `list<Entity>`（§3.1.1，`HydratesEntityRows`） |
| 对外列表项 / 分页行 | Service 内 `fromEntityRow` / `fromEntity` | `array<int, XxxDto>` 或 `*PageResult` |
| 纯 ID | `listIdsByCronTaskId` | `array<int, int>` |
| 插入 | `insert` / `create` | `Entity` |
| 保存已有实体 | `save` | `Entity` |
| 条件更新 | `updateById` / `updateHeartbeat` | `Entity` 或 `int` |
| CAS | `casTransition` / `casHeartbeat` / `casRecoverLease` | `bool` |
| 软删 | `softDelete` | `Entity` |
| 硬删 / 清关联 | `deleteByUserId` / `purgeExpired` | `int` |

Repository 的 `listRows*` 只返回 `list<Entity>`；`fromEntityRow` / `fromEntity` 在 **Service**（或极少数 Response 组装处）完成，Service 不依赖 Repository 吐出的关联数组行。

富化（节点组名、创建人、任务数）在 Service 组合多个 Repository：本表 `list<Entity>` + 对方 `findById` / `listBriefRowsByIds` / `count*`，再写入 Row Dto 或 `getAttributes()` 后的数组。不要把多表 join 塞进一个「万能」Repository 以返回 Dto。

推荐：

```text
CronAgentNodeRepository::listAll()
    → array<int, CronAgentNodeRowDto>   // 本表字段

CronTaskManagerService::listNodes()
    → 调 NodeRepo + GroupRepo + TaskRepo.countByNodeIds
    → 往已有 Node Dto 填 groupName / taskCount
```

或给 `CronAgentNodeRowDto` 增加 `withGroup()` / setter，由 Service 填。不要为了少一次往返把三表 join 写进一个「万能」Repository。

---

## 7. 需要补的 Dto

已有 `fromEntityRow` 的继续用，并尽量加 `fromEntity(Entity $e): static`（内部 `toArray()` 再复用现映射）。**新建类路径遵循 §3.2.1**（Controller 目录或 `Dto/Common/`）。

必须新增、消灭裸数组的：

| 现返回 | 改为（建议路径） |
|---|---|
| `CronRobotService` 列表/详情 `array` | 已有 `Dto/CronRobot/CronRobotRowDto.php` |
| `listRoleOptions` `array{id,name,code,isSuper}` | `Dto/StaffRole/StaffRoleOptionDto.php` 或 `Dto/Common/StaffRoleOptionDto.php`（下拉多入口则 Common） |
| `listUsersByNodeGroup` `array{id,account,userName}` | `Dto/Common/StaffUserBriefDto.php`（Cron 节点组选人 + Staff 均可能用） |
| `nodeGroupsByIds` `array{id,groupName}` | `Dto/Common/CronAgentNodeGroupBriefDto.php`（Staff 校验 + Cron 展示） |
| `rolesGroupedByUserIds` 内层 `array` | `Dto/Common/StaffRoleBriefDto.php`，整体 `array<int, array<int, StaffRoleBriefDto>>` |
| `roleStats` 裸数组 | `Dto/StaffRole/StaffRoleStatsDto.php` |
| `testRobot` `array{ok,error}` | `Dto/CronRobot/` 下已有或补测试 Ack Dto |
| `getUser` / `getRole` / `getMenu` / `me` 的 `array<string, mixed>` | 扩 `Dto/StaffUser/StaffUserRowDto`、`Dto/StaffRole/StaffRoleRowDto`、`Dto/StaffRole/StaffMenuRowDto` 等，不再 `getAttributes()` 直接出站 |
| `menusForUser` / `grantedMenuUris` | 菜单行 `Dto/StaffRole/StaffMenuRowDto.php`（或 Common）；纯 URI 列表保持 `array<int, string>`（标量例外） |

Worker 拉任务（`CronTaskService::fetchCronTask`）现在返回引擎要的 `list<array>`（`ScheduleEvent` 元数据）。这是**调度协议，不是 Admin 行对象**：

- Repository 只提供 `array<int, CronTaskEntity>` 或 `array<int, CronTaskRowDto>`
- `CronTaskService` 再 `fetchShellCronTask` / `fetchHttpCronTask` / `fetchK8sCronTask` 转成引擎数组

不要让 Repository 返回 CronManager 的运行时数组。

---

## 8. 特殊路径（不要用普通 CRUD 硬套）

### 8.1 Execution Lease / RunOnce

`ExecutionService` 的 CAS 整段迁入 `CronTaskLogRepository`：

- `findById` / `findByBatch` / `findLatestByRequestId` → `?CronTaskLogEntity`
- `insertExecution` → `CronTaskLogEntity`
- `casHeartbeat` / `casTransition` / `casRecoverLease` → `bool`
- `listExpiredLeases` → `array<int, CronTaskLogEntity>`（随后每条再 CAS；这是「待处理实体集合」，允许 Entity 列表）

**例外**：Recovery 候选行要带着 `lease_owner` / `lease_until` 原样做 CAS，用 Entity 比先转 Dto 再读回更安全。  
「单条查 → Entity；多条展示 → Dto」仍然成立；这里是**多条待写回的工作集**，返回 `array<int, CronTaskLogEntity>`。

`precheckRunOnce` / `claimRunOnce` 的分支判断留在 `ExecutionService`。

### 8.2 Slot Claim 事务

`CronScheduledTaskRecordService::claimOnce` 的事务边界留在 Service（或该 Service 降级为薄编排）：

```text
BEGIN
  CronTaskRepository::findByIdForUpdate()
  CronScheduledTaskRecordRepository::findInSkewWindow()
  CronScheduledTaskRecordRepository::insert()
COMMIT
```

`findByIdForUpdate` 仍返回 `?CronTaskEntity`。1062 判定留在 Record Repository。

### 8.3 用户 / 角色关联替换

`replaceUserRoles` 等整段迁入对应 junction Repository，Service 只调 `replace(userId, roleIds)`。

### 8.4 权限范围

`scopedTaskIds` / `viewerAuthorizedNodeGroupIds` 的**规则**在 Service（或 StaffUserService），**SQL** 在 Repository：

```php
// Service
$groupIds = $this->staffUserService->viewerAuthorizedNodeGroupIds($viewer);
$nodeIds  = $this->nodeRepo->listIdsByGroupIds($groupIds);
$tasks    = $this->taskRepo->listByQuery($query, $nodeIds);
```

Repository 不引用 `StaffUserService`。

---

## 9. Service 迁完后长什么样

```php
// CronTaskManagerService::getNode
public function getNode(NodeIdDto $dto): CronAgentNodeRowDto
{
    $node = $this->nodeRepo->findById($dto->getId());
    if ($node === null) {
        throw CronTaskException::throw('节点不存在', -1);
    }
    $row = CronAgentNodeRowDto::fromEntity($node);
    $row->setTaskCount($this->taskRepo->countByNodeId($node->id));
    // groupName 由 GroupRepo 补
    return $row;
}
```

```php
// StaffAuthService::login
$user = $this->userRepo->findByLoginIdentity($dto->getAccount());
if ($user === null || $user->isDisabled()) {
    throw ...;
}
```

Service 可以持有多个 Repository。现有构造方式（`new XxxService()`）保持简单：Service 内 `new XxxRepository()`，或后续再引入容器，不在本次范围。

---

## 10. 实施顺序

按「表依赖少、调用面集中」推进，每步保持可运行。

```text
1. 补齐 Entity::loadById / loadByXxx
        ↓
2. Staff 七个 Repository
   （User → Role/Menu → junction）
   Cron 对 StaffUserEntity 的直接查询改为 StaffUserRepository
        ↓
3. Cron 基础表
   NodeGroup → Node → Robot → Task
        ↓
4. cron_task_log + Execution CAS
   （P0 Recovery / RunOnce 语义不得回归）
        ↓
5. RunRequest / OperationLog / ScheduleRecord / AlertLog
        ↓
6. Service 去掉残余 Entity::query
   KubernetesCrashRecovery 改走 TaskRepo
        ↓
7. 静态检查：Module/Cron、Module/Staff 的 Service/Controller
   不得再出现 Entity::query / ->select() / ->find()
```

每一步要求：

- 单条路径改为 `?Entity`
- Repository 整行列表改为 `list<Entity>`（共用 `HydratesEntityRows`）；Service 再映射为 `array<int, XxxDto>` / `*PageResult`
- 不改 API JSON 字段名（Dto `fromEntityRow` 已对齐 camelCase）
- 新 Dto 按 §3.2.1 落盘（StaffAuth / StaffUser / StaffRole / CronTaskManager / CronRobot / Common）

---

## 11. 验收

### 结构

- 存在 `Module/Cron/Repository/*`、`Module/Staff/Repository/*`
- 一张表对应一个 Repository
- Dto：`Module/*/Dto/{Controller短名}/` 与 Controller 一一对应；跨控制器共享在 `Module/*/Dto/Common/`
- Service / Controller / Middleware 无 `Entity::query`

### 类型

- 单条查询返回 `?Entity` 或 Service 层 `Entity`（require）
- Repository 整行列表返回 `list<Entity>`（§3.1.1，`HydratesEntityRows`），禁止 `list<array>`
- Service / Response 对外列表、分页 `list` 为 Dto 数组（§3.2），不是 `list<array>` 裸行
- 投影 / 纯 id / 聚合仍按 §3.3 标量或 id 集合
- 详情 / `me` 不再返回 `getAttributes()` 裸数组

### 行为不变

- SoftDelete 默认过滤
- `staff_roles.code` 软删后可复用
- Execution Lease CAS、RunOnce UNIQUE、Slot UNIQUE 仍在 Repository 内用同一套 WHERE
- 节点组权限范围与现在一致
- Admin / Agent HTTP 响应字段不变

### 明确不做

- 不引入接口 + 实现双文件（先具体类）
- 不引入 Query Builder 对外泄漏（Repository 方法不返回 Query）
- 不改表结构、不改软删列名
- 不把 Worker 调度元数据数组塞进 Repository
- 不把 Response / Feign SDK 生成放进本次

---

## 12. 现状对照（迁出量）

| 模块 | Entity | 当前打库集中地 | 建议 Repository 数 |
|---|---|---|---|
| Cron | 9 张表 | `CronTaskManagerService`（最大）、`ExecutionService`、`CronTaskService`、`CronRobotService`、`CronAlertService`、`CronScheduledTaskRecordService` | 9 |
| Staff | 7 张表 | `StaffRoleService`（最大）、`StaffUserService`、`StaffAuthService`、`StaffMenuPermissionService` | 7 |

Controller 已无直查，迁完后只改 Service 调用点。

---

## 13. 结论

Repository 是 **SQL 边界**，不是新的业务层。

```text
单条：Entity::loadById / loadByXxx → ?Entity
多条 / 聚合：Dto，列表必须 array<int, XxxDto>
Dto 目录：一 Controller 一子目录；共享放 Dto/Common/
主键集合：array<int, int>（唯一标量例外）
写入：只发生在 Repository
跨模块：只调对方 Repository，禁止跨模块 Entity::query
```

按第 10 节顺序落地，先 Staff 再 Cron 基础表，最后动 `cron_task_log` 的 CAS，避免和执行正确性 P0 缠在一起。
