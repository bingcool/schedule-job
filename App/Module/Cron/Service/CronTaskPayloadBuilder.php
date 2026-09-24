<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use Swoolefy\Worker\Cron\ExpressionParser;
use Swoolefy\Worker\Cron\KubernetesJobSpec;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\Common\CronTaskPayloadBuildResultDto;
use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\Common\CronTaskPayloadDto;
use App\Module\Cron\ShellCommandGuard;

/**
 * 将原始 payload 数组规范为 {@see CronTaskPayloadDto}。
 *
 * ## 设计
 * - **不依赖** HTTP Request；入参为 snake_case 数组（与 Request::toPayloadArray() 对齐）
 * - 创建态（$isCreate=true）：强制 name/expression/command、合法 exec_type、node_id
 * - 更新态：仅 put 有值/合法字段，空更新由上层根据 {@see CronTaskPayloadDto::isEmpty} 拒绝
 * - Shell（exec_type=1）command 经 {@see ShellCommandGuard} 黑名单检查，拒绝则不写库
 * - Kubernetes（exec_type=3）走 {@see KubernetesJobSpec} 结构校验，并强制 timeout>0
 * - 成功/失败统一包在 {@see CronTaskPayloadBuildResultDto}，不抛异常
 */
class CronTaskPayloadBuilder
{
    /** exec_type=3 的 command 只存 argv，长度受表列 varchar(256) 约束。 */
    private const COMMAND_MAX_LENGTH = 256;

    /**
     * 构建可持久化的任务字段集合。
     *
     * @param array<string, mixed> $payload snake_case：name、expression、command、exec_type、node_id、
     *        description、status、with_block_lapping、retry、timeout、http_*、cron_between、cron_skip 等
     * @param bool $isCreate true=创建校验；false=部分更新（缺省字段不写入 DTO）
     */
    public function build(array $payload, bool $isCreate): CronTaskPayloadBuildResultDto
    {
        $name = trim((string)($payload['name'] ?? ''));
        $expression = trim((string)($payload['expression'] ?? ''));
        $command = trim((string)($payload['command'] ?? ''));
        $description = trim((string)($payload['description'] ?? ''));
        $nodeId = isset($payload['node_id']) ? (int)$payload['node_id'] : null;
        $execType = isset($payload['exec_type']) ? (int)$payload['exec_type'] : null;
        $status = isset($payload['status']) ? (int)$payload['status'] : null;
        $withBlockLapping = isset($payload['with_block_lapping']) ? (int)$payload['with_block_lapping'] : null;
        $retry = array_key_exists('retry', $payload) ? (int)$payload['retry'] : null;
        $timeout = array_key_exists('timeout', $payload) ? (int)$payload['timeout'] : null;
        $httpMethod = strtoupper(trim((string)($payload['http_method'] ?? 'GET')));
        $httpTimeout = isset($payload['http_request_time_out']) ? (int)$payload['http_request_time_out'] : null;
        $cronBetween = $this->normalizeTimeRanges($payload['cron_between'] ?? null);
        $cronSkip = $this->normalizeTimeRanges($payload['cron_skip'] ?? null);
        $httpBody = $this->normalizeJsonField($payload['http_body'] ?? null);
        $httpHeaders = $this->normalizeJsonField($payload['http_headers'] ?? null);
        $rawK8sSpec = $this->normalizeJsonField($payload['k8s_spec'] ?? null);

        // Kubernetes 配置：校验结构，并把 command 列写成 argv（不拼 namespace/deployment）
        $k8sSpec = null;
        if (is_array($rawK8sSpec) && $rawK8sSpec !== []) {
            try {
                $parsed = KubernetesJobSpec::fromArray($rawK8sSpec);
            } catch (\Throwable $e) {
                return CronTaskPayloadBuildResultDto::fail($e->getMessage());
            }
            if ($parsed->command === [] || $parsed->args === []) {
                return CronTaskPayloadBuildResultDto::fail('exec_type=3时k8s_spec.command与args为必填');
            }
            $k8sSpec = $this->canonicalK8sSpec($parsed);
            $command = $this->k8sCommandLine($parsed);
        }

        if ($isCreate) {
            $commandRequired = $execType !== CronTaskPayloadDto::EXEC_TYPE_K8S;
            if ($name === '' || $expression === '' || ($commandRequired && $command === '')) {
                return CronTaskPayloadBuildResultDto::fail('name/expression/command为必填');
            }
            if (!in_array($execType, CronTaskPayloadDto::EXEC_TYPES, true)) {
                return CronTaskPayloadBuildResultDto::fail('exec_type仅支持1(shell)、2(http)和3(kubernetes)');
            }
            if ($nodeId <= 0) {
                return CronTaskPayloadBuildResultDto::fail('node_id为必填');
            }
        }

        if ($execType === CronTaskPayloadDto::EXEC_TYPE_K8S) {
            if ($isCreate && $k8sSpec === null) {
                return CronTaskPayloadBuildResultDto::fail('exec_type=3时k8s_spec为必填');
            }
            // Executor 是「一个协程等到 Job 结束」的同步模型，没有上限就等于永久占用
            // 一个协程和该任务的 with_block_lapping 执行权，因此这里硬性要求 timeout
            if ($isCreate && ($timeout === null || $timeout <= 0)) {
                return CronTaskPayloadBuildResultDto::fail('exec_type=3时timeout必须>0，用于界定等待Job的上限');
            }
            if (!$isCreate && $timeout !== null && $timeout <= 0) {
                return CronTaskPayloadBuildResultDto::fail('exec_type=3时timeout必须>0，用于界定等待Job的上限');
            }
        }

        if ($expression !== '') {
            $exprError = $this->validateExpression($expression);
            if ($exprError !== null) {
                return CronTaskPayloadBuildResultDto::fail($exprError);
            }
        }

        if ($retry !== null && $retry < 0) {
            return CronTaskPayloadBuildResultDto::fail('retry必须是>=0的整数');
        }
        if ($timeout !== null && $timeout < 0) {
            return CronTaskPayloadBuildResultDto::fail('timeout必须是>=0的整数');
        }

        if ($this->shouldCheckShellCommand($execType, $command)) {
            $deny = ShellCommandGuard::denyReason($command);
            if ($deny !== null) {
                return CronTaskPayloadBuildResultDto::fail($deny);
            }
        }

        $dto = new CronTaskPayloadDto();

        if ($name !== '') {
            $dto->putName($name);
        }
        if ($expression !== '') {
            $dto->putExpression($expression);
        }
        if ($command !== '' || $k8sSpec !== null) {
            $dto->putCommand($command);
        }
        if ($description !== '' || $isCreate) {
            $dto->putDescription($description);
        }
        if ($nodeId !== null && $nodeId > 0) {
            $dto->putNodeId($nodeId);
        }
        if ($execType !== null && in_array($execType, CronTaskPayloadDto::EXEC_TYPES, true)) {
            $dto->putExecType($execType);
        }
        if ($status !== null && in_array($status, [0, 1], true)) {
            $dto->putStatus($status);
        }
        if ($withBlockLapping !== null && in_array($withBlockLapping, [0, 1], true)) {
            $dto->putWithBlockLapping($withBlockLapping);
        }
        if ($retry !== null) {
            $dto->putRetry(min(1, max(0, $retry)));
        } elseif ($isCreate) {
            $dto->putRetry(0);
        }
        if ($timeout !== null) {
            $dto->putTimeout(max(0, $timeout));
        } elseif ($isCreate) {
            $dto->putTimeout(0);
        }

        if ($httpMethod !== '' || $isCreate) {
            $dto->putHttpMethod($httpMethod === '' ? 'GET' : $httpMethod);
        }
        if ($httpTimeout !== null && $httpTimeout >= 0) {
            $dto->putHttpRequestTimeOut($httpTimeout);
        } elseif ($isCreate) {
            $dto->putHttpRequestTimeOut(30);
        }

        if ($cronBetween !== null || $isCreate) {
            $dto->putCronBetween($cronBetween);
        }
        if ($cronSkip !== null || $isCreate) {
            $dto->putCronSkip($cronSkip);
        }
        if ($httpBody !== null || $isCreate) {
            $dto->putHttpBody(is_array($httpBody) ? $httpBody : null);
        }
        if ($httpHeaders !== null || $isCreate) {
            $dto->putHttpHeaders(is_array($httpHeaders) ? $httpHeaders : null);
        }

        // 创建时始终落一次（type 1/2 写 null，避免残留脏配置）；更新时只在提交了才动
        if ($k8sSpec !== null || $isCreate) {
            $dto->putK8sSpec($k8sSpec);
        }

        return CronTaskPayloadBuildResultDto::ok($dto);
    }

    /**
     * type 3 的 command 列只存 argv，不拼 Namespace / Deployment / Container。
     */
    protected function k8sCommandLine(KubernetesJobSpec $spec): string
    {
        $argv = array_merge($spec->command, $spec->args);
        if ($argv === []) {
            return '';
        }

        return mb_substr(implode(' ', $argv), 0, self::COMMAND_MAX_LENGTH);
    }

    /**
     * 把校验通过的 {@see KubernetesJobSpec} 收敛成落库用的规范结构。
     *
     * 只写回被识别的字段，UI 传来的多余键不入库；`command`/`args` 只在真的覆盖时才写，
     * 保留「未设置」与「显式设为空数组」的区别（前者沿用镜像 ENTRYPOINT/CMD）。
     *
     * @return array<string, mixed>
     */
    protected function canonicalK8sSpec(KubernetesJobSpec $spec): array
    {
        $canonical = [
            'namespace' => $spec->namespace,
            'deployment' => $spec->deployment,
            'container' => $spec->container,
        ];
        if ($spec->overridesCommand) {
            $canonical['command'] = $spec->command;
        }
        if ($spec->overridesArgs) {
            $canonical['args'] = $spec->args;
        }

        return $canonical;
    }

    /**
     * 规范化 JSON 类字段：空 → null；字符串尝试 json_decode；数组原样返回。
     *
     * @return array<string, mixed>|mixed|null
     */
    protected function normalizeJsonField(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * 规范化时间段列表为 `[['start'=>..., 'end'=>...], ...]`。
     *
     * 支持已是数组，或 JSON 字符串；缺 start/end 的项丢弃；全无效则 null。
     *
     * @return array<int, array{start: string, end: string}>|null
     */
    protected function normalizeTimeRanges(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }
        if (!is_array($value)) {
            return null;
        }

        $ranges = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }
            $start = trim((string)($item['start'] ?? ''));
            $end = trim((string)($item['end'] ?? ''));
            if ($start === '' || $end === '') {
                continue;
            }
            $ranges[] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return !empty($ranges) ? $ranges : null;
    }

    /**
     * Shell 任务才检查 command；HTTP URL 与 Kubernetes 跳过。
     *
     * Kubernetes 跳过的理由：{@see ShellCommandGuard} 是**本机 Shell** 黑名单，
     * 而 type 3 的 command 列只是 argv 展示，真正执行的是集群里的 argv 数组（不过 Shell）。
     *
     * 部分更新未带 exec_type 时，以 http(s) URL 判断，避免误伤。
     */
    protected function shouldCheckShellCommand(?int $execType, string $command): bool
    {
        if ($command === '') {
            return false;
        }
        if ($execType === CronTaskPayloadDto::EXEC_TYPE_HTTP || $execType === CronTaskPayloadDto::EXEC_TYPE_K8S) {
            return false;
        }
        if ($execType === CronTaskPayloadDto::EXEC_TYPE_SHELL) {
            return true;
        }

        return preg_match('#^https?://#i', $command) !== 1;
    }

    /**
     * 用引擎 ExpressionParser 校验表达式，避免 Web API 再实现一套解析器。
     *
     * 秒级 Interval 实际下限由 IntervalSchedule 约束（>=5）；非法 Linux Cron 原样返回引擎文案。
     */
    protected function validateExpression(string $expression): ?string
    {
        try {
            (new ExpressionParser())->parse($expression);
        } catch (\Throwable $e) {
            return 'expression无效: ' . $e->getMessage();
        }

        return null;
    }
}
