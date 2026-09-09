<?php

declare(strict_types=1);

namespace App\Module\Cron\Robot;

/**
 * Strategy 只消费本对象，禁止再查 cron_task / log。
 */
final class RobotAlertMessage
{
    public function __construct(
        public readonly bool $isTest,
        public readonly int $executionId,
        public readonly int $cronId,
        public readonly string $execBatchId,
        public readonly string $taskName,
        public readonly int $nodeId,
        public readonly string $nodeName,
        public readonly int $nodeGroupId,
        public readonly string $nodeGroupName,
        public readonly string $status,
        public readonly string $failureReason,
        public readonly string $scheduledAt,
        public readonly string $startedAt,
        public readonly string $finishedAt,
        public readonly int $durationMs,
        public readonly ?int $exitCode,
        public readonly ?int $httpStatus,
        public readonly int $triggerType,
        public readonly string $command,
    ) {
    }

    public static function connectivityTest(): self
    {
        return new self(
            isTest: true,
            executionId: 0,
            cronId: 0,
            execBatchId: '',
            taskName: '',
            nodeId: 0,
            nodeName: '',
            nodeGroupId: 0,
            nodeGroupName: '',
            status: '',
            failureReason: '',
            scheduledAt: '',
            startedAt: '',
            finishedAt: '',
            durationMs: 0,
            exitCode: null,
            httpStatus: null,
            triggerType: 0,
            command: '',
        );
    }

    public function testText(): string
    {
        return '机器人连通测试';
    }

    public function alertTitle(): string
    {
        return 'Cron 任务告警 · ' . $this->statusLabel();
    }

    public function statusLabel(): string
    {
        return $this->status !== '' ? $this->status : 'UNKNOWN';
    }

    /**
     * 纯文本正文（飞书 text / 钉钉 text 测试用）。
     */
    public function toPlainText(): string
    {
        if ($this->isTest) {
            return $this->testText();
        }

        $lines = [
            $this->alertTitle(),
            '任务：' . $this->dash($this->taskName),
            '状态：' . $this->statusLabel(),
        ];
        if ($this->failureReason !== '') {
            $lines[] = '原因：' . $this->failureReason;
        }
        $lines[] = '节点：' . $this->dash($this->nodeName) . ' (#' . $this->nodeId . ')';
        if ($this->nodeGroupName !== '') {
            $lines[] = '节点组：' . $this->nodeGroupName;
        }
        $lines[] = '开始：' . $this->dash($this->startedAt);
        $lines[] = '结束：' . $this->dash($this->finishedAt);
        $lines[] = '耗时：' . $this->formatDuration();
        if ($this->exitCode !== null) {
            $lines[] = '退出码：' . $this->exitCode;
        }
        if ($this->httpStatus !== null) {
            $lines[] = 'HTTP：' . $this->httpStatus;
        }
        $lines[] = 'Command：' . $this->dash($this->command);
        $lines[] = 'Execution：#' . $this->executionId;
        if ($this->execBatchId !== '') {
            $lines[] = '批次：' . $this->execBatchId;
        }

        return implode("\n", $lines);
    }

    /**
     * 企微 / 钉钉 markdown。企微 markdown 支持 <font color="warning">。
     */
    public function toMarkdown(bool $wecomColor = false): string
    {
        if ($this->isTest) {
            return $this->testText();
        }

        $status = $this->statusLabel();
        if ($wecomColor) {
            $color = strtoupper($status) === 'TIMEOUT' ? 'warning' : 'warning';
            $statusCell = '<font color="' . $color . '">' . $status . '</font>';
        } else {
            $statusCell = '**' . $status . '**';
        }

        $lines = [
            '## ' . $this->alertTitle(),
            '',
            '> 状态：' . $statusCell,
            '> 任务：' . $this->dash($this->taskName),
        ];
        if ($this->failureReason !== '') {
            $lines[] = '> 原因：' . $this->failureReason;
        }
        $lines[] = '> 节点：' . $this->dash($this->nodeName) . ' (#' . $this->nodeId . ')';
        if ($this->nodeGroupName !== '') {
            $lines[] = '> 节点组：' . $this->nodeGroupName;
        }
        $lines[] = '> 开始：' . $this->dash($this->startedAt);
        $lines[] = '> 结束：' . $this->dash($this->finishedAt);
        $lines[] = '> 耗时：' . $this->formatDuration();
        if ($this->exitCode !== null) {
            $lines[] = '> 退出码：' . $this->exitCode;
        }
        if ($this->httpStatus !== null) {
            $lines[] = '> HTTP：' . $this->httpStatus;
        }
        $lines[] = '> Command：`' . $this->dash($this->command) . '`';
        $lines[] = '> Execution：#' . $this->executionId;
        if ($this->execBatchId !== '') {
            $lines[] = '> 批次：' . $this->execBatchId;
        }

        return implode("\n", $lines);
    }

    /**
     * 飞书富文本 post。
     *
     * @return array<string, mixed>
     */
    public function toFeishuPost(): array
    {
        if ($this->isTest) {
            return [
                'zh_cn' => [
                    'title' => $this->testText(),
                    'content' => [
                        [['tag' => 'text', 'text' => $this->testText()]],
                    ],
                ],
            ];
        }

        $rows = [
            $this->feishuLine('状态', $this->statusLabel()),
            $this->feishuLine('任务', $this->dash($this->taskName)),
        ];
        if ($this->failureReason !== '') {
            $rows[] = $this->feishuLine('原因', $this->failureReason);
        }
        $rows[] = $this->feishuLine('节点', $this->dash($this->nodeName) . ' (#' . $this->nodeId . ')');
        if ($this->nodeGroupName !== '') {
            $rows[] = $this->feishuLine('节点组', $this->nodeGroupName);
        }
        $rows[] = $this->feishuLine('开始', $this->dash($this->startedAt));
        $rows[] = $this->feishuLine('结束', $this->dash($this->finishedAt));
        $rows[] = $this->feishuLine('耗时', $this->formatDuration());
        if ($this->exitCode !== null) {
            $rows[] = $this->feishuLine('退出码', (string) $this->exitCode);
        }
        if ($this->httpStatus !== null) {
            $rows[] = $this->feishuLine('HTTP', (string) $this->httpStatus);
        }
        $rows[] = $this->feishuLine('Command', $this->dash($this->command));
        $rows[] = $this->feishuLine('Execution', '#' . $this->executionId);
        if ($this->execBatchId !== '') {
            $rows[] = $this->feishuLine('批次', $this->execBatchId);
        }

        return [
            'zh_cn' => [
                'title' => $this->alertTitle(),
                'content' => $rows,
            ],
        ];
    }

    /**
     * @return list<array{tag: string, text: string}>
     */
    private function feishuLine(string $label, string $value): array
    {
        return [
            ['tag' => 'text', 'text' => $label . '：' . $value],
        ];
    }

    private function dash(string $value): string
    {
        $value = trim($value);

        return $value !== '' ? $value : '-';
    }

    private function formatDuration(): string
    {
        if ($this->durationMs <= 0) {
            return '-';
        }
        if ($this->durationMs < 1000) {
            return $this->durationMs . 'ms';
        }

        return round($this->durationMs / 1000, 2) . 's';
    }
}
