<?php

declare(strict_types=1);

namespace InterfaceApi\Support\Generator;

/**
 * generate-client 终端输出（TTY 下启用 ANSI，非 TTY 纯文本）。
 */
final class ConsoleReporter
{
    private bool $color;

    public function __construct(private string $projectRoot)
    {
        $this->color = function_exists('stream_isatty') && @stream_isatty(STDOUT);
    }

    public function banner(string $title = 'InterfaceApi Client Generator'): void
    {
        $this->out('');
        $this->out($this->style($title, 'bold'));
        $this->out($this->style(str_repeat('─', 42), 'dim'));
    }

    public function meta(string $label, string $value): void
    {
        $this->out(sprintf('  %-12s %s', $this->style($label, 'dim') . ':', $value));
    }

    public function section(string $title): void
    {
        $this->out('');
        $this->out($this->style('▸ ' . $title, 'bold'));
    }

    public function ok(string $message, ?string $detail = null): void
    {
        $line = '  ' . $this->style('✓', 'green') . ' ' . $message;
        if ($detail !== null && $detail !== '') {
            $line .= '  ' . $this->style($detail, 'dim');
        }
        $this->out($line);
    }

    public function info(string $message): void
    {
        $this->out('  ' . $this->style('·', 'dim') . ' ' . $message);
    }

    public function skip(string $message): void
    {
        $this->out('  ' . $this->style('○', 'yellow') . ' ' . $this->style($message, 'dim'));
    }

    public function warn(string $message): void
    {
        $this->out('  ' . $this->style('!', 'yellow') . ' ' . $message);
    }

    public function error(string $message): void
    {
        $this->out('  ' . $this->style('✗', 'red') . ' ' . $message);
    }

    public function summary(
        float $seconds,
        int $writtenCount,
        int $skippedCount,
        int $removedStale,
        string $writtenLabel = 'client(s) written',
    ): void {
        $this->out('');
        $this->out($this->style(str_repeat('─', 42), 'dim'));
        $time = number_format($seconds, 2);
        $tail = [(string) $writtenCount . ' ' . $writtenLabel];
        if ($skippedCount > 0) {
            $tail[] = $skippedCount . ' skipped';
        }
        if ($removedStale > 0) {
            $tail[] = $removedStale . ' stale removed';
        }
        $line = 'Done in ' . $time . 's — ' . implode(', ', $tail);
        $this->out('');
        $this->out($this->style($line, 'highlight'));
        $this->out('');
    }

    public function relPath(string $absolutePath): string
    {
        $root = realpath($this->projectRoot);
        $abs = realpath($absolutePath) ?: $absolutePath;
        if ($root !== false && str_starts_with($abs, $root)) {
            return ltrim(substr($abs, strlen($root)), DIRECTORY_SEPARATOR . '/\\');
        }

        return $absolutePath;
    }

    public function shortInterfaceName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function out(string $line): void
    {
        fwrite(STDOUT, $line . PHP_EOL);
    }

    private function style(string $text, string $kind): string
    {
        if (!$this->color) {
            return $text;
        }

        return match ($kind) {
            'bold' => "\033[1m{$text}\033[0m",
            'dim' => "\033[2m{$text}\033[0m",
            'green' => "\033[32m{$text}\033[0m",
            'yellow' => "\033[33m{$text}\033[0m",
            'cyan' => "\033[36m{$text}\033[0m",
            'red' => "\033[31m{$text}\033[0m",
            // 粗体 + 高亮绿（终端无法真正放大字号，用加粗与高对比突出收尾行）
            'highlight' => "\033[1;92m{$text}\033[0m",
            default => $text,
        };
    }
}
