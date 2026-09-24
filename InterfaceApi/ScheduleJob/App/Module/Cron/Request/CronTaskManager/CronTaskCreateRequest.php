<?php

declare(strict_types=1);


namespace InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\Common\CronTimeRangeDto;
use InvalidArgumentException;
use stdClass;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class CronTaskCreateRequest extends BaseRequest
{
    #[ApiProperty(description: '任务名称')]
    #[ValidationRule(rule: 'required|string', message: 'name 不能为空')]
    protected string $name = '';

    #[ApiProperty(description: 'Cron 表达式')]
    #[ValidationRule(rule: 'required|string', message: 'expression 不能为空')]
    protected string $expression = '';

    /**
     * exec_type=1 是 shell 命令，=2 是 URL，=3 只是展示摘要（留空则由 k8sSpec 自动生成）。
     * 因此这里不能标 required，必填性交给 CronTaskPayloadBuilder 统一判定。
     */
    #[ApiProperty(description: '执行命令或 URL；exec_type=3 可留空，由 k8sSpec 生成摘要')]
    protected string $command = '';

    #[ApiProperty(description: '执行类型：1 shell，2 http，3 kubernetes')]
    #[ValidationRule(rule: 'required|int', message: 'execType 不能为空')]
    protected int $execType = 0;

    #[ApiProperty(description: '节点 ID')]
    #[ValidationRule(rule: 'required|int', message: 'nodeId 不能为空')]
    #[StringToInt]
    protected int $nodeId = 0;

    #[ApiProperty(description: '描述')]
    protected ?string $description = null;

    #[ApiProperty(description: '状态：0 禁用，1 启用')]
    protected ?int $status = null;

    #[ApiProperty(description: '是否阻塞重叠执行：0 否，1 是')]
    protected ?int $withBlockLapping = null;

    #[ApiProperty(description: '失败后重试次数（不含首次；0=不重试）')]
    protected ?int $retry = null;

    #[ApiProperty(description: 'Shell 执行超时秒数，0=不限制')]
    protected ?int $timeout = null;

    #[ApiProperty(description: 'HTTP 方法')]
    protected ?string $httpMethod = null;

    #[ApiProperty(description: 'HTTP 超时（秒）')]
    protected ?int $httpRequestTimeOut = null;

    /**
     * @var array<int, CronTimeRangeDto>
     */
    #[ApiProperty(description: '允许执行时间段列表')]
    #[ValidationRule(rule: 'nullable|array', message: 'cronBetween 格式错误', itemClass: CronTimeRangeDto::class)]
    protected array $cronBetween = [];

    /**
     * @var array<int, CronTimeRangeDto>
     */
    #[ApiProperty(description: '需跳过的时间段列表')]
    #[ValidationRule(rule: 'nullable|array', message: 'cronSkip 格式错误', itemClass: CronTimeRangeDto::class)]
    protected array $cronSkip = [];

    /**
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'HTTP 请求体（JSON 对象）')]
    protected ?array $httpBody = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'HTTP 请求头（JSON 对象）')]
    protected ?array $httpHeaders = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'exec_type=3 的 Kubernetes 配置：namespace/deployment/container/command/args')]
    protected ?array $k8sSpec = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression(string $expression): static
    {
        $this->expression = $expression;

        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getExecType(): int
    {
        return $this->execType;
    }

    public function setExecType(int $execType): static
    {
        $this->execType = $execType;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getWithBlockLapping(): ?int
    {
        return $this->withBlockLapping;
    }

    public function setWithBlockLapping(?int $withBlockLapping): static
    {
        $this->withBlockLapping = $withBlockLapping;

        return $this;
    }

    public function getRetry(): ?int
    {
        return $this->retry;
    }

    public function setRetry(?int $retry): static
    {
        $this->retry = $retry;

        return $this;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function setTimeout(?int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(?string $httpMethod): static
    {
        $this->httpMethod = $httpMethod;

        return $this;
    }

    public function getHttpRequestTimeOut(): ?int
    {
        return $this->httpRequestTimeOut;
    }

    public function setHttpRequestTimeOut(?int $httpRequestTimeOut): static
    {
        $this->httpRequestTimeOut = $httpRequestTimeOut;

        return $this;
    }

    /**
     * @return array<int, CronTimeRangeDto>
     */
    public function getCronBetween(): array
    {
        return $this->cronBetween;
    }

    /**
     * @param array<int, CronTimeRangeDto|array<string, mixed>|stdClass>|string|null $cronBetween
     */
    public function setCronBetween(mixed $cronBetween): static
    {
        $this->cronBetween = $this->normalizeTimeRanges($cronBetween, 'cronBetween') ?? [];

        return $this;
    }

    public function addCronBetween(CronTimeRangeDto $item): static
    {
        $this->cronBetween[] = $item;

        return $this;
    }

    /**
     * @return array<int, CronTimeRangeDto>
     */
    public function getCronSkip(): array
    {
        return $this->cronSkip;
    }

    /**
     * @param array<int, CronTimeRangeDto|array<string, mixed>|stdClass>|string|null $cronSkip
     */
    public function setCronSkip(mixed $cronSkip): static
    {
        $this->cronSkip = $this->normalizeTimeRanges($cronSkip, 'cronSkip') ?? [];

        return $this;
    }

    public function addCronSkip(CronTimeRangeDto $item): static
    {
        $this->cronSkip[] = $item;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getHttpBody(): ?array
    {
        return $this->httpBody;
    }

    /**
     * @param array<string, mixed>|null $httpBody
     */
    public function setHttpBody(?array $httpBody): static
    {
        $this->httpBody = $httpBody;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getHttpHeaders(): ?array
    {
        return $this->httpHeaders;
    }

    /**
     * @param array<string, mixed>|null $httpHeaders
     */
    public function setHttpHeaders(?array $httpHeaders): static
    {
        $this->httpHeaders = $httpHeaders;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getK8sSpec(): ?array
    {
        return $this->k8sSpec;
    }

    /**
     * 结构校验留给 {@see \Swoolefy\Worker\Cron\KubernetesJobSpec}，这里只负责把
     * JSON 字符串形式也接住（表单直接 POST 字符串时不至于静默丢字段）。
     *
     * @param array<string, mixed>|string|stdClass|null $k8sSpec
     */
    public function setK8sSpec(mixed $k8sSpec): static
    {
        if (is_string($k8sSpec)) {
            $decoded = json_decode($k8sSpec, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('k8sSpec must be a JSON object');
            }
            $k8sSpec = $decoded;
        }
        if ($k8sSpec instanceof stdClass) {
            $k8sSpec = get_object_vars($k8sSpec);
        }
        if ($k8sSpec !== null && !is_array($k8sSpec)) {
            throw new InvalidArgumentException('k8sSpec must be an object or null');
        }
        $this->k8sSpec = $k8sSpec;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayloadArray(): array
    {
        return [
            'name' => $this->getName(),
            'expression' => $this->getExpression(),
            'command' => $this->getCommand(),
            'exec_type' => $this->getExecType(),
            'node_id' => $this->getNodeId(),
            'description' => $this->getDescription(),
            'status' => $this->getStatus(),
            'with_block_lapping' => $this->getWithBlockLapping(),
            'retry' => $this->getRetry(),
            'timeout' => $this->getTimeout(),
            'http_method' => $this->getHttpMethod(),
            'http_request_time_out' => $this->getHttpRequestTimeOut(),
            'cron_between' => $this->serializeTimeRanges($this->getCronBetween()),
            'cron_skip' => $this->serializeTimeRanges($this->getCronSkip()),
            'http_body' => $this->getHttpBody(),
            'http_headers' => $this->getHttpHeaders(),
            'k8s_spec' => $this->getK8sSpec(),
        ];
    }

    /**
     * @param array<int, CronTimeRangeDto> $ranges
     * @return array<int, array{start: string, end: string}>|null
     */
    protected function serializeTimeRanges(array $ranges): ?array
    {
        if ($ranges === []) {
            return null;
        }

        $out = [];
        foreach ($ranges as $row) {
            if ($row instanceof CronTimeRangeDto) {
                $out[] = [
                    'start' => $row->getStart(),
                    'end' => $row->getEnd(),
                ];
            }
        }

        return $out === [] ? null : $out;
    }

    /**
     * @param array<int, CronTimeRangeDto|array<string, mixed>|stdClass>|string|null $ranges
     * @return array<int, CronTimeRangeDto>|null
     */
    protected function normalizeTimeRanges(mixed $ranges, string $fieldName): ?array
    {
        if ($ranges === null || $ranges === []) {
            return $ranges;
        }

        if (is_string($ranges)) {
            $decoded = json_decode($ranges, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $ranges = $decoded;
            }
        }

        if (!is_array($ranges)) {
            throw new InvalidArgumentException(sprintf('%s must be an array, JSON string, or null', $fieldName));
        }

        $normalized = [];
        foreach ($ranges as $item) {
            if ($item instanceof CronTimeRangeDto) {
                $normalized[] = $item;
                continue;
            }

            if ($item instanceof stdClass) {
                $item = get_object_vars($item);
            }

            if (!is_array($item)) {
                throw new InvalidArgumentException(sprintf('%s items must be CronTimeRangeDto or {start,end} objects', $fieldName));
            }

            $start = trim((string)($item['start'] ?? ''));
            $end = trim((string)($item['end'] ?? ''));
            if ($start === '' || $end === '') {
                throw new InvalidArgumentException(sprintf('%s items require non-empty start and end', $fieldName));
            }

            $dto = new CronTimeRangeDto();
            $dto->setStart($start)->setEnd($end);
            $normalized[] = $dto;
        }

        return $normalized;
    }
}
