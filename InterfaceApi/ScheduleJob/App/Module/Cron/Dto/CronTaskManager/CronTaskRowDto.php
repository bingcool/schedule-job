<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

/**
 * Cron 任务列表/详情行 DTO。
 *
 * **职责**：表示 cron_task 表的单条任务记录，字段与数据库查询结果对齐（camelCase 输出）。
 *
 * **生产者**：{@see \InterfaceApi\ScheduleJob\App\Module\Cron\Service\CronTaskManagerService::listTasks} 通过
 * {@see static::fromEntityRow} 映射查询行；{@see \InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\CronTaskRowResponse}
 * 在 create/update 后同样调用 fromEntityRow。
 *
 * **消费者**：{@see \InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager\ListTasksPageResult} 收集列表项；
 * API 序列化时通过 ApiProperty 注解生成文档。
 *
 * **关键字段语义**：
 * - execType：1=shell，2=http
 * - status：0=禁用，1=启用
 * - withBlockLapping：0=允许重叠执行，1=阻塞重叠执行
 * - command：shell 时为脚本路径，http 时为请求 URL
 * - cronBetween / cronSkip：允许/跳过执行的时间段 JSON 数组
 * - nextRunAt：下次合法执行 unix 秒（展示层推算，不落库）；禁用/非法表达式为 null
 * - nextRunAtAt：同上的 datetime 串（Y-m-d H:i:s），无下次执行时为空
 * - nodeName：绑定节点名称（cron_agent_node.node_name）；节点已软删仍尽量回填，找不到则为空
 * - groupId / groupName：绑定节点所属分组；未分组时 groupId=0、groupName 为空
 */
class CronTaskRowDto extends AbstractDto
{
    #[ApiProperty(description: '任务 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '节点 ID')]
    protected int $nodeId = 0;

    #[ApiProperty(description: '节点名称；节点已软删仍尽量回填，找不到则为空')]
    protected string $nodeName = '';

    #[ApiProperty(description: '绑定节点所属分组 ID；未分组时为 0')]
    protected int $groupId = 0;

    #[ApiProperty(description: '绑定节点所属分组名称；未分组时为空')]
    protected string $groupName = '';

    #[ApiProperty(description: '绑定节点状态：online / offline')]
    protected string $nodeStatus = 'offline';

    #[ApiProperty(description: '任务名称')]
    protected string $name = '';

    #[ApiProperty(description: 'Cron 表达式')]
    protected string $expression = '';

    #[ApiProperty(description: '执行命令或 URL')]
    protected string $command = '';

    #[ApiProperty(description: '执行类型：1=shell, 2=http')]
    protected int $execType = 1;

    #[ApiProperty(description: '任务状态：0=禁用, 1=启用')]
    protected int $status = 1;

    #[ApiProperty(description: '是否阻塞重叠执行：0=否, 1=是')]
    protected int $withBlockLapping = 0;

    #[ApiProperty(description: '失败后重试次数（不含首次；0=不重试）')]
    protected int $retry = 0;

    #[ApiProperty(description: 'Shell 执行超时秒数，0=不限制')]
    protected int $timeout = 0;

    #[ApiProperty(description: '表达式类型：interval=每N秒，cron=Linux Cron（展示层派生，不落库）')]
    protected string $expressionType = 'interval';

    #[ApiProperty(description: '任务描述')]
    protected string $description = '';

    /**
     * 允许执行的时间段列表。
     *
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: '允许执行时间段列表')]
    protected array $cronBetween = [];

    /**
     * 跳过执行的时间段列表。
     *
     * @var array<int, array<string, mixed>>
     */
    #[ApiProperty(description: '跳过执行时间段列表')]
    protected array $cronSkip = [];

    #[ApiProperty(description: 'HTTP 请求方法')]
    protected string $httpMethod = 'GET';

    /**
     * HTTP 请求体。
     *
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'HTTP 请求体')]
    protected ?array $httpBody = null;

    /**
     * HTTP 请求头。
     *
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'HTTP 请求头')]
    protected ?array $httpHeaders = null;

    #[ApiProperty(description: 'HTTP 请求超时时间（秒）')]
    protected int $httpRequestTimeOut = 30;

    /**
     * exec_type=3 的 Kubernetes 配置。
     *
     * 里面只有 namespace/deployment/container/command/args，不含镜像与凭证，
     * 因此不需要像 http_headers 那样做敏感信息脱敏。
     *
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'Kubernetes 配置：namespace/deployment/container/command/args')]
    protected ?array $k8sSpec = null;

    #[ApiProperty(description: '创建人 staff_user.id')]
    protected int $createdBy = 0;

    #[ApiProperty(description: '创建人名称')]
    protected string $createdByName = '';

    #[ApiProperty(description: '下次合法执行 unix 秒；禁用或非法表达式为 null（暂停，无下次调度）')]
    protected ?int $nextRunAt = null;

    #[ApiProperty(description: '下次合法执行时间（Y-m-d H:i:s）；无则空串，风格同 createdAt / lastHeartbeatAt')]
    protected string $nextRunAtAt = '';

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    #[ApiProperty(description: '更新时间')]
    protected string $updatedAt = '';

    

    /**
     * 列表 JSON 必须带上节点名称与分组；显式写出，避免 toArray 漏字段。
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['nodeName'] = $this->getNodeName();
        $out['groupId'] = $this->getGroupId();
        $out['groupName'] = $this->getGroupName();
        $out['nodeStatus'] = $this->getNodeStatus();
        $out['createdBy'] = $this->getCreatedBy();
        $out['createdByName'] = $this->getCreatedByName();
        $out['createdAt'] = $this->getCreatedAt();
        $out['updatedAt'] = $this->getUpdatedAt();

        return $out;
    }

    

    /** 获取任务 ID */
    public function getId(): int
    {
        return $this->id;
    }

    /** 设置任务 ID */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /** 获取节点 ID */
    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    /** 设置节点 ID */
    public function setNodeId(int $nodeId): static
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    /** 获取节点名称 */
    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /** 设置节点名称 */
    public function setNodeName(string $nodeName): static
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /** 获取绑定节点所属分组 ID */
    public function getGroupId(): int
    {
        return $this->groupId;
    }

    /** 设置绑定节点所属分组 ID */
    public function setGroupId(int $groupId): static
    {
        $this->groupId = $groupId;

        return $this;
    }

    /** 获取绑定节点所属分组名称 */
    public function getGroupName(): string
    {
        return $this->groupName;
    }

    /** 设置绑定节点所属分组名称 */
    public function setGroupName(string $groupName): static
    {
        $this->groupName = $groupName;

        return $this;
    }

    /** 获取绑定节点状态 */
    public function getNodeStatus(): string
    {
        return $this->nodeStatus;
    }

    /** 设置绑定节点状态 */
    public function setNodeStatus(string $nodeStatus): static
    {
        $this->nodeStatus = $nodeStatus;

        return $this;
    }

    /** 获取任务名称 */
    public function getName(): string
    {
        return $this->name;
    }

    /** 设置任务名称 */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** 获取 Cron 表达式 */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /** 设置 Cron 表达式 */
    public function setExpression(string $expression): static
    {
        $this->expression = $expression;

        return $this;
    }

    /** 获取执行命令或 URL */
    public function getCommand(): string
    {
        return $this->command;
    }

    /** 设置执行命令或 URL */
    public function setCommand(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    /** 获取执行类型 */
    public function getExecType(): int
    {
        return $this->execType;
    }

    /** 设置执行类型 */
    public function setExecType(int $execType): static
    {
        $this->execType = $execType;

        return $this;
    }

    /** 获取任务状态 */
    public function getStatus(): int
    {
        return $this->status;
    }

    /** 设置任务状态 */
    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    

    

    /** 获取是否阻塞重叠执行 */
    public function getWithBlockLapping(): int
    {
        return $this->withBlockLapping;
    }

    /** 设置是否阻塞重叠执行 */
    public function setWithBlockLapping(int $withBlockLapping): static
    {
        $this->withBlockLapping = $withBlockLapping;

        return $this;
    }

    /** 获取失败后重试次数 */
    public function getRetry(): int
    {
        return $this->retry;
    }

    /** 设置失败后重试次数 */
    public function setRetry(int $retry): static
    {
        $this->retry = min(1, max(0, $retry));

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): static
    {
        $this->timeout = max(0, $timeout);

        return $this;
    }

    /** 获取表达式展示类型 */
    public function getExpressionType(): string
    {
        return $this->expressionType;
    }

    /** 设置表达式展示类型 */
    public function setExpressionType(string $expressionType): static
    {
        $this->expressionType = $expressionType;

        return $this;
    }

    /** 获取任务描述 */
    public function getDescription(): string
    {
        return $this->description;
    }

    /** 设置任务描述 */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * 获取允许执行时间段列表。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCronBetween(): array
    {
        return $this->cronBetween;
    }

    /**
     * 设置允许执行时间段列表。
     *
     * @param array<int, array<string, mixed>> $cronBetween
     */
    public function setCronBetween(array $cronBetween): static
    {
        $this->cronBetween = $cronBetween;

        return $this;
    }

    /**
     * 获取跳过执行时间段列表。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCronSkip(): array
    {
        return $this->cronSkip;
    }

    /**
     * 设置跳过执行时间段列表。
     *
     * @param array<int, array<string, mixed>> $cronSkip
     */
    public function setCronSkip(array $cronSkip): static
    {
        $this->cronSkip = $cronSkip;

        return $this;
    }

    /** 获取 HTTP 请求方法 */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /** 设置 HTTP 请求方法 */
    public function setHttpMethod(string $httpMethod): static
    {
        $this->httpMethod = $httpMethod;

        return $this;
    }

    /**
     * 获取 HTTP 请求体。
     *
     * @return array<string, mixed>|null
     */
    public function getHttpBody(): ?array
    {
        return $this->httpBody;
    }

    /**
     * 设置 HTTP 请求体。
     *
     * @param array<string, mixed>|null $httpBody
     */
    public function setHttpBody(?array $httpBody): static
    {
        $this->httpBody = $httpBody;

        return $this;
    }

    /**
     * 获取 HTTP 请求头。
     *
     * @return array<string, mixed>|null
     */
    public function getHttpHeaders(): ?array
    {
        return $this->httpHeaders;
    }

    /**
     * 设置 HTTP 请求头。
     *
     * @param array<string, mixed>|null $httpHeaders
     */
    public function setHttpHeaders(?array $httpHeaders): static
    {
        $this->httpHeaders = $httpHeaders;

        return $this;
    }

    /**
     * 获取 Kubernetes 配置。
     *
     * @return array<string, mixed>|null
     */
    public function getK8sSpec(): ?array
    {
        return $this->k8sSpec;
    }

    /**
     * 设置 Kubernetes 配置。
     *
     * @param array<string, mixed>|null $k8sSpec
     */
    public function setK8sSpec(?array $k8sSpec): static
    {
        $this->k8sSpec = $k8sSpec;

        return $this;
    }

    /** 获取 HTTP 请求超时时间（秒） */
    public function getHttpRequestTimeOut(): int
    {
        return $this->httpRequestTimeOut;
    }

    /** 设置 HTTP 请求超时时间（秒） */
    public function setHttpRequestTimeOut(int $httpRequestTimeOut): static
    {
        $this->httpRequestTimeOut = $httpRequestTimeOut;

        return $this;
    }

    /** 获取创建人 ID */
    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    /** 设置创建人 ID */
    public function setCreatedBy(int $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /** 获取创建人名称 */
    public function getCreatedByName(): string
    {
        return $this->createdByName;
    }

    /** 设置创建人名称 */
    public function setCreatedByName(string $createdByName): static
    {
        $this->createdByName = $createdByName;

        return $this;
    }

    /** 获取下次合法执行 unix 秒；无则为 null */
    public function getNextRunAt(): ?int
    {
        return $this->nextRunAt;
    }

    /** 设置下次合法执行 unix 秒 */
    public function setNextRunAt(?int $nextRunAt): static
    {
        $this->nextRunAt = $nextRunAt;

        return $this;
    }

    /** 获取下次合法执行 datetime 串；无则为空 */
    public function getNextRunAtAt(): string
    {
        return $this->nextRunAtAt;
    }

    /** 设置下次合法执行 datetime 串 */
    public function setNextRunAtAt(string $nextRunAtAt): static
    {
        $this->nextRunAtAt = $nextRunAtAt;

        return $this;
    }

    /** 获取创建时间 */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /** 设置创建时间 */
    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** 获取更新时间 */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /** 设置更新时间 */
    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
