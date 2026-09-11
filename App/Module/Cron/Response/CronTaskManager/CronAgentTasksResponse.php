<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class CronAgentTasksResponse extends BaseResponse
{
    #[ApiProperty(description: '节点 ID')]
    protected int $nodeId;

    #[ApiProperty(description: '任务条数')]
    protected int $total;

    #[ApiProperty(description: '执行类型（单类型查询时返回）')]
    protected ?int $execType = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: '任务列表（指定 execType 时）')]
    protected ?array $list = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: 'Shell 任务列表')]
    protected ?array $shellTasks = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: 'HTTP 任务列表')]
    protected ?array $httpTasks = null;

    /**
     * @var array<int, mixed>|null
     */
    #[ApiProperty(description: 'Kubernetes 任务列表')]
    protected ?array $k8sTasks = null;

    /**
     * @param array<int, mixed> $list
     */
    public static function forExecType(int $nodeId, int $execType, array $list): self
    {
        $self = new self();
        $self->setNodeId($nodeId);
        $self->setExecType($execType);
        $self->setList($list);
        $self->setTotal(count($list));
        $self->setShellTasks(null);
        $self->setHttpTasks(null);
        $self->setK8sTasks(null);

        return $self;
    }

    /**
     * @param array<int, mixed> $shellTasks
     * @param array<int, mixed> $httpTasks
     * @param array<int, mixed> $k8sTasks
     */
    public static function forAllTypes(int $nodeId, array $shellTasks, array $httpTasks, array $k8sTasks = []): self
    {
        $self = new self();
        $self->setNodeId($nodeId);
        $self->setExecType(null);
        $self->setList(null);
        $self->setShellTasks($shellTasks);
        $self->setHttpTasks($httpTasks);
        $self->setK8sTasks($k8sTasks);
        $self->setTotal(count($shellTasks) + count($httpTasks) + count($k8sTasks));

        return $self;
    }

    public function getNodeId(): int
    {
        return $this->nodeId;
    }

    public function setNodeId(int $nodeId): static
    {
        $this->nodeId = $nodeId;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getExecType(): ?int
    {
        return $this->execType;
    }

    public function setExecType(?int $execType): static
    {
        $this->execType = $execType;

        return $this;
    }

    /**
     * @return array<int, mixed>|null
     */
    public function getList(): ?array
    {
        return $this->list;
    }

    /**
     * @param array<int, mixed>|null $list
     */
    public function setList(?array $list): static
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return array<int, mixed>|null
     */
    public function getShellTasks(): ?array
    {
        return $this->shellTasks;
    }

    /**
     * @param array<int, mixed>|null $shellTasks
     */
    public function setShellTasks(?array $shellTasks): static
    {
        $this->shellTasks = $shellTasks;

        return $this;
    }

    /**
     * @return array<int, mixed>|null
     */
    public function getHttpTasks(): ?array
    {
        return $this->httpTasks;
    }

    /**
     * @param array<int, mixed>|null $httpTasks
     */
    public function setHttpTasks(?array $httpTasks): static
    {
        $this->httpTasks = $httpTasks;

        return $this;
    }

    /**
     * @return array<int, mixed>|null
     */
    public function getK8sTasks(): ?array
    {
        return $this->k8sTasks;
    }

    /**
     * @param array<int, mixed>|null $k8sTasks
     */
    public function setK8sTasks(?array $k8sTasks): static
    {
        $this->k8sTasks = $k8sTasks;

        return $this;
    }

    public function getData(): array
    {
        if ($this->getList() !== null) {
            return [
                'nodeId' => $this->getNodeId(),
                'execType' => $this->getExecType(),
                'total' => $this->getTotal(),
                'list' => $this->getList(),
            ];
        }

        return [
            'nodeId' => $this->getNodeId(),
            'total' => $this->getTotal(),
            'shellTasks' => $this->getShellTasks() ?? [],
            'httpTasks' => $this->getHttpTasks() ?? [],
            'k8sTasks' => $this->getK8sTasks() ?? [],
        ];
    }
}
