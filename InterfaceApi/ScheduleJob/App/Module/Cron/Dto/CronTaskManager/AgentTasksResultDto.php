<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * Agent 拉取待执行任务结果 DTO。
 *
 * **职责**：按查询条件返回 Agent 可消费的调度元数据列表，支持「单类型」与「全类型」两种响应形态。
 *
 * **生产者**：{@see \InterfaceApi\ScheduleJob\App\Module\Cron\Service\CronTaskManagerService::agentTasks} 根据
 * execType 调用 {@see static::forExecType} 或 {@see static::forAllTypes} 构造。
 *
 * **消费者**：{@see \InterfaceApi\ScheduleJob\App\Module\Cron\Controller\CronTaskManagerController::agentTasks} 将本 DTO
 * 作为 {@see \InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronAgentTasksResponse} 的 data 返回。
 *
 * **关键字段语义**（互斥填充）：
 * - 指定 execType（1 / 2 / 3）时：list 有值，shellTasks / httpTasks / k8sTasks 为 null
 * - 未指定 execType 时：shellTasks / httpTasks / k8sTasks 有值，list 为 null
 * - 列表元素为 ScheduleEvent / CronUrlTaskMeta 的 toArray() 调度元数据
 */
class AgentTasksResultDto extends AbstractDto
{
    #[ApiProperty(description: '回显的 Agent 节点 ID')]
    protected int $nodeId = 0;

    #[ApiProperty(description: '查询时指定的执行类型；全类型查询时为 null')]
    protected ?int $execType = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: '单 execType 模式下的任务列表')]
    protected ?array $list = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: '全类型模式下的 shell 任务列表')]
    protected ?array $shellTasks = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: '全类型模式下的 http 任务列表')]
    protected ?array $httpTasks = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: '全类型模式下的 kubernetes 任务列表')]
    protected ?array $k8sTasks = null;

    #[ApiProperty(description: '任务总条数')]
    protected int $total = 0;

    /**
     * 构造单执行类型查询结果。
     *
     * 当 Agent 指定 execType=1（shell）/ 2（http）/ 3（kubernetes）时使用；
     * 仅填充 list，其余分类列表保持 null。
     *
     * @param int $nodeId Agent 节点 ID
     * @param int $execType 执行类型：1=shell，2=http，3=kubernetes
     * @param array<int, mixed> $list 该类型的调度元数据列表
     */
    public static function forExecType(int $nodeId, int $execType, array $list): self
    {
        $dto = new self();
        $dto->nodeId = $nodeId;
        $dto->execType = $execType;
        $dto->list = $list;
        $dto->total = count($list);

        return $dto;
    }

    /**
     * 构造全类型查询结果。
     *
     * 当 Agent 未指定合法 execType 时使用；
     * 同时返回 shell / http / kubernetes 三类任务，list 保持 null。
     *
     * @param int $nodeId Agent 节点 ID
     * @param array<int, mixed> $shellTasks shell 类型调度元数据列表
     * @param array<int, mixed> $httpTasks http 类型调度元数据列表
     * @param array<int, mixed> $k8sTasks kubernetes 类型调度元数据列表
     */
    public static function forAllTypes(int $nodeId, array $shellTasks, array $httpTasks, array $k8sTasks = []): self
    {
        $dto = new self();
        $dto->nodeId = $nodeId;
        $dto->shellTasks = $shellTasks;
        $dto->httpTasks = $httpTasks;
        $dto->k8sTasks = $k8sTasks;
        $dto->total = count($shellTasks) + count($httpTasks) + count($k8sTasks);

        return $dto;
    }

    /** 获取节点 ID */
    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    /** 获取查询时的执行类型（全类型模式为 null） */
    public function getExecType(): ?int
    {
        return $this->execType;
    }

    /**
     * 获取单类型任务列表（仅 forExecType 模式有值）。
     *
     * @return array<int, mixed>|null
     */
    public function getList(): ?array
    {
        return $this->list;
    }

    /**
     * 获取 shell 任务列表（仅 forAllTypes 模式有值）。
     *
     * @return array<int, mixed>|null
     */
    public function getShellTasks(): ?array
    {
        return $this->shellTasks;
    }

    /**
     * 获取 http 任务列表（仅 forAllTypes 模式有值）。
     *
     * @return array<int, mixed>|null
     */
    public function getHttpTasks(): ?array
    {
        return $this->httpTasks;
    }

    /**
     * 获取 kubernetes 任务列表（仅 forAllTypes 模式有值）。
     *
     * @return array<int, mixed>|null
     */
    public function getK8sTasks(): ?array
    {
        return $this->k8sTasks;
    }

    /**
     * 判断是否为单 execType 响应形态（list 非 null）。
     */
    public function isSingleExecType(): bool
    {
        return $this->list !== null;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
