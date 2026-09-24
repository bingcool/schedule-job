<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronTaskRowDto;
use Swoolefy\Worker\Cron\CronNextRunAt;

final class CronTaskRowMapper
{
    /**
         * 从数据库实体行（snake_case）映射为 DTO。
         *
         * JSON 列（cron_between、cron_skip、http_body、http_headers）在非法类型时
         * 分别回退为空数组或 null。
         *
         * nextRunAt 由 {@see CronNextRunAt::compute} 按引擎规则推算，不读 Worker 内存。
         *
         * @param array<string, mixed> $row cron_task 查询行或实体 getAttributes() 结果
         * @param int|null $now 推算基准 unix 秒；空则 time()。单测可注入以对齐网格
         */
        public static function fromEntityRow(array $row, ?int $now = null): CronTaskRowDto
        {
            $dto = new CronTaskRowDto();
            $dto->setId((int)($row['id'] ?? 0));
            $dto->setNodeId((int) self::pick($row, 'node_id', 'nodeId', 0));
            $dto->setNodeName((string) self::pick($row, 'node_name', 'nodeName', ''));
            $dto->setGroupId((int) self::pick($row, 'group_id', 'groupId', 0));
            $dto->setGroupName((string) self::pick($row, 'group_name', 'groupName', ''));
            $dto->setNodeStatus((string) self::pick($row, 'node_status', 'nodeStatus', 'offline'));
            // 表列为 cron_name，兼容误用 name 的查询结果
            $dto->setName((string)($row['name'] ?? $row['cron_name'] ?? ''));
            $dto->setExpression((string)($row['expression'] ?? ''));
            $dto->setCommand((string)($row['command'] ?? ''));
            $dto->setExecType((int)($row['exec_type'] ?? 1));
            $dto->setStatus((int)($row['status'] ?? 1));
            $dto->setWithBlockLapping((int)($row['with_block_lapping'] ?? 0));
            $dto->setRetry(max(0, (int)($row['retry'] ?? 0)));
            $dto->setTimeout(max(0, (int)($row['timeout'] ?? 0)));
            $dto->setExpressionType(self::deriveExpressionType((string)($row['expression'] ?? '')));
            $dto->setDescription((string)($row['description'] ?? ''));
            $cb = $row['cron_between'] ?? [];
            $dto->setCronBetween(is_array($cb) ? $cb : []);
            $cs = $row['cron_skip'] ?? [];
            $dto->setCronSkip(is_array($cs) ? $cs : []);
            $dto->setHttpMethod((string)($row['http_method'] ?? 'GET'));
            $hb = $row['http_body'] ?? null;
            $dto->setHttpBody(is_array($hb) ? $hb : null);
            $hh = $row['http_headers'] ?? null;
            $dto->setHttpHeaders(self::maskSensitiveHeaders(is_array($hh) ? $hh : null));
            $dto->setHttpRequestTimeOut((int)($row['http_request_time_out'] ?? 30));
            $ks = $row['k8s_spec'] ?? $row['k8sSpec'] ?? null;
            if ($ks instanceof \stdClass) {
                $ks = get_object_vars($ks);
            }
            if (is_string($ks) && $ks !== '') {
                $decoded = json_decode($ks, true);
                $ks = is_array($decoded) ? $decoded : null;
            }
            $dto->setK8sSpec(is_array($ks) ? $ks : null);
            $dto->setCreatedBy((int) self::pick($row, 'created_by', 'createdBy', 0));
            $dto->setCreatedByName((string) self::pick($row, 'created_by_name', 'createdByName', ''));
            $dto->setCreatedAt((string)($row['created_at'] ?? ''));
            $dto->setUpdatedAt((string)($row['updated_at'] ?? ''));

            $computeRow = $row;
            $computeRow['status'] = $dto->getStatus();
            $computeRow['expression'] = $dto->getExpression();
            $computeRow['cron_name'] = $dto->getName();
            $computeRow['command'] = $dto->getCommand();
            $computeRow['cron_between'] = $dto->getCronBetween();
            $computeRow['cron_skip'] = $dto->getCronSkip();
            $next = CronNextRunAt::compute($computeRow, $now);
            $dto->setNextRunAt($next);
            $dto->setNextRunAtAt(CronNextRunAt::formatDatetime($next));

            return $dto;
        }

    /**
         * @param array<string, mixed> $row
         */
        private static function pick(array $row, string $snake, string $camel, mixed $default): mixed
        {
            if (array_key_exists($snake, $row) && $row[$snake] !== null) {
                return $row[$snake];
            }
            if (array_key_exists($camel, $row) && $row[$camel] !== null) {
                return $row[$camel];
            }

            return $default;
        }

    /**
         * 由 expression 派生展示类型：纯数字 → interval，否则 cron。
         */
        public static function deriveExpressionType(string $expression): string
        {
            $expression = trim($expression);

            return $expression !== '' && ctype_digit($expression) ? 'interval' : 'cron';
        }

    /**
         * 列表/详情不回传 Authorization/Cookie/Token 等敏感 Header 明文。
         *
         * @param array<string, mixed>|null $headers
         * @return array<string, mixed>|null
         */
        public static function maskSensitiveHeaders(?array $headers): ?array
        {
            if ($headers === null) {
                return null;
            }

            $sensitive = ['authorization', 'cookie', 'token', 'x-api-key', 'api-key', 'password', 'secret'];
            $masked = [];
            foreach ($headers as $key => $value) {
                $lower = strtolower((string)$key);
                $hit = false;
                foreach ($sensitive as $needle) {
                    if ($lower === $needle || str_contains($lower, $needle)) {
                        $hit = true;
                        break;
                    }
                }
                $masked[$key] = $hit ? '******' : $value;
            }

            return $masked;
        }
}
