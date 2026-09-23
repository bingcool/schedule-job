<?php

declare(strict_types=1);


namespace App\Module\Cron\Request\CronTaskManager;

use App\Module\Cron\Dto\Common\CronTimeRangeDto;
use InvalidArgumentException;
use stdClass;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class CronTaskUpdateRequest extends BaseRequest
{
    #[ApiProperty(description: '任务 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    #[ApiProperty(description: '任务名称')]
    protected ?string $name = null;

    #[ApiProperty(description: 'Cron 表达式')]
    protected ?string $expression = null;

    #[ApiProperty(description: '执行命令或 URL')]
    protected ?string $command = null;

    #[ApiProperty(description: '描述')]
    protected ?string $description = null;

    #[ApiProperty(description: '节点 ID')]
    #[StringToInt]
    protected ?int $nodeId = null;

    #[ApiProperty(description: '执行类型：1 shell，2 http，3 kubernetes')]
    protected ?int $execType = null;

    #[ApiProperty(description: '状态：0 禁用，1 启用')]
    protected ?int $status = null;

    #[ApiProperty(description: '是否阻塞重叠执行')]
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
     * @var array<int, CronTimeRangeDto>|null
     */
    #[ApiProperty(description: '允许执行时间段列表')]
    #[ValidationRule(rule: 'nullable|array', message: 'cronBetween 格式错误', itemClass: CronTimeRangeDto::class)]
    protected ?array $cronBetween = null;

    /**
     * @var array<int, CronTimeRangeDto>|null
     */
    #[ApiProperty(description: '需跳过的时间段列表')]
    #[ValidationRule(rule: 'nullable|array', message: 'cronSkip 格式错误', itemClass: CronTimeRangeDto::class)]
    protected ?array $cronSkip = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'HTTP 请求体')]
    protected ?array $httpBody = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'HTTP 请求头')]
    protected ?array $httpHeaders = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ApiProperty(description: 'exec_type=3 的 Kubernetes 配置：namespace/deployment/container/command/args')]
    protected ?array $k8sSpec = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getExpression(): ?string
    {
        return $this->expression;
    }

    public function setExpression(?string $expression): static
    {
        $this->expression = $expression;

        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(?string $command): static
    {
        $this->command = $command;

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

    public function getNodeId(): ?int
    {
        return $this->nodeId;
    }

    public function setNodeId(?int $nodeId): static
    {
        $this->nodeId = $nodeId;

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
     * @return array<int, CronTimeRangeDto>|null
     */
    public function getCronBetween(): ?array
    {
        return $this->cronBetween;
    }

    /**
     * @param array<int, CronTimeRangeDto|array<string, mixed>|stdClass>|string|null $cronBetween
     */
    public function setCronBetween(mixed $cronBetween): static
    {
        $this->cronBetween = $this->normalizeTimeRanges($cronBetween, 'cronBetween');

        return $this;
    }

    public function addCronBetween(CronTimeRangeDto $item): static
    {
        if ($this->cronBetween === null) {
            $this->cronBetween = [];
        }
        $this->cronBetween[] = $item;

        return $this;
    }

    /**
     * @return array<int, CronTimeRangeDto>|null
     */
    public function getCronSkip(): ?array
    {
        return $this->cronSkip;
    }

    /**
     * @param array<int, CronTimeRangeDto|array<string, mixed>|stdClass>|string|null $cronSkip
     */
    public function setCronSkip(mixed $cronSkip): static
    {
        $this->cronSkip = $this->normalizeTimeRanges($cronSkip, 'cronSkip');

        return $this;
    }

    public function addCronSkip(CronTimeRangeDto $item): static
    {
        if ($this->cronSkip === null) {
            $this->cronSkip = [];
        }
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
     * 结构校验留给 {@see \Swoolefy\Worker\Cron\KubernetesJobSpec}，这里只接住 JSON 字符串形式。
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
        $out = [];
        if ($this->name !== null) {
            $out['name'] = $this->name;
        }
        if ($this->expression !== null) {
            $out['expression'] = $this->expression;
        }
        if ($this->command !== null) {
            $out['command'] = $this->command;
        }
        if ($this->description !== null) {
            $out['description'] = $this->description;
        }
        if ($this->nodeId !== null) {
            $out['node_id'] = $this->nodeId;
        }
        if ($this->execType !== null) {
            $out['exec_type'] = $this->execType;
        }
        if ($this->status !== null) {
            $out['status'] = $this->status;
        }
        if ($this->withBlockLapping !== null) {
            $out['with_block_lapping'] = $this->withBlockLapping;
        }
        if ($this->retry !== null) {
            $out['retry'] = $this->retry;
        }
        if ($this->timeout !== null) {
            $out['timeout'] = $this->timeout;
        }
        if ($this->httpMethod !== null) {
            $out['http_method'] = $this->httpMethod;
        }
        if ($this->httpRequestTimeOut !== null) {
            $out['http_request_time_out'] = $this->httpRequestTimeOut;
        }
        if ($this->cronBetween !== null) {
            $out['cron_between'] = $this->serializeTimeRanges($this->cronBetween);
        }
        if ($this->cronSkip !== null) {
            $out['cron_skip'] = $this->serializeTimeRanges($this->cronSkip);
        }
        if ($this->httpBody !== null) {
            $out['http_body'] = $this->httpBody;
        }
        if ($this->httpHeaders !== null) {
            $out['http_headers'] = $this->httpHeaders;
        }
        if ($this->k8sSpec !== null) {
            $out['k8s_spec'] = $this->k8sSpec;
        }

        return $out;
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
