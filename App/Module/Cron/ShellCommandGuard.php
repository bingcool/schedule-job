<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * Shell 任务 command 的 best-effort 黑名单。
 *
 * 只在写库前调用；null=通过，字符串=拒绝原因（中文短句）。
 * 不解析完整 Shell AST，不能当沙箱。
 */
final class ShellCommandGuard
{
    /** @var array<string, true> */
    private const DENIED_COMMANDS = [
        'rm' => true,
        'unlink' => true,
        'shred' => true,
        'srm' => true,
        'wipe' => true,
        'dd' => true,
        'mkfs' => true,
        'mkfs.ext2' => true,
        'mkfs.ext3' => true,
        'mkfs.ext4' => true,
        'mkfs.xfs' => true,
        'mkfs.btrfs' => true,
        'fdisk' => true,
        'cfdisk' => true,
        'sfdisk' => true,
        'parted' => true,
        'wipefs' => true,
        'blkdiscard' => true,
        'debugfs' => true,
        'mount' => true,
        'umount' => true,
        'losetup' => true,
        'shutdown' => true,
        'reboot' => true,
        'halt' => true,
        'poweroff' => true,
        'init' => true,
        'useradd' => true,
        'userdel' => true,
        'usermod' => true,
        'groupadd' => true,
        'groupdel' => true,
        'groupmod' => true,
        'passwd' => true,
        'chpasswd' => true,
        'newusers' => true,
        'sudo' => true,
        'su' => true,
        'doas' => true,
        'pkexec' => true,
        'runuser' => true,
        'systemctl' => true,
        'service' => true,
        'rc-service' => true,
        'rc-update' => true,
        'iptables' => true,
        'ip6tables' => true,
        'nft' => true,
        'firewall-cmd' => true,
        'ufw' => true,
        'sysctl' => true,
        'modprobe' => true,
        'insmod' => true,
        'rmmod' => true,
    ];

    /** @var array<string, true> */
    private const SHELL_WRAPPERS = [
        'bash' => true,
        'sh' => true,
        'zsh' => true,
        'ksh' => true,
        'dash' => true,
    ];

    /** @var array<string, true> */
    private const PYTHON_WRAPPERS = [
        'python' => true,
        'python3' => true,
    ];

    /**
     * @return string|null 拒绝原因；null=通过
     */
    public static function denyReason(string $command): ?string
    {
        $command = trim($command);
        if ($command === '') {
            return null;
        }

        $patternDeny = self::denyByFixedPatterns(strtolower($command));
        if ($patternDeny !== null) {
            return $patternDeny;
        }

        foreach (self::splitSegments($command) as $segment) {
            $deny = self::denySegment($segment);
            if ($deny !== null) {
                return $deny;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function splitSegments(string $command): array
    {
        $parts = preg_split('/\s*(?:&&|\|\||[;|\n\r])\s*/', $command, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [$command];
        }

        return $parts;
    }

    private static function denySegment(string $segment): ?string
    {
        $segment = self::stripWrappingQuotes(trim($segment));
        if ($segment === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $segment, 2) ?: [];
        $first = self::stripWrappingQuotes((string) ($parts[0] ?? ''));
        if ($first === '') {
            return null;
        }

        $name = strtolower(basename($first));
        $rest = (string) ($parts[1] ?? '');
        $tokens = self::argTokens($rest);

        if (isset(self::SHELL_WRAPPERS[$name]) && self::hasShortFlag($tokens, 'c')) {
            return '不允许使用 ' . $name . ' -c';
        }
        if (isset(self::PYTHON_WRAPPERS[$name]) && self::hasShortFlag($tokens, 'c')) {
            return '不允许使用 ' . $name . ' -c';
        }
        if ($name === 'php' && (self::hasShortFlag($tokens, 'r') || self::hasShortFlag($tokens, 'c'))) {
            $flag = self::hasShortFlag($tokens, 'r') ? '-r' : '-c';

            return '不允许使用 php ' . $flag;
        }

        if (isset(self::DENIED_COMMANDS[$name])) {
            return '不允许使用命令 ' . $name;
        }

        return null;
    }

    private static function denyByFixedPatterns(string $lower): ?string
    {
        if (preg_match('/\b(?:curl|wget)\b[^;\n]*\|\s*(?:\S*\/)?(?:bash|sh|zsh)\b/', $lower) === 1) {
            return '不允许管道执行远程脚本';
        }
        if (preg_match('/\bfind\b[\s\S]*?\s-delete(?:\s|$|[;&|])/', $lower) === 1) {
            return '不允许使用 find -delete';
        }
        if (preg_match('/\bfind\b[\s\S]*?\s-exec\s+.*\brm\b/', $lower) === 1) {
            return '不允许使用 find -exec rm';
        }
        if (preg_match('/\bxargs\b(?:\s+\S+)*\s+rm\b/', $lower) === 1) {
            return '不允许使用 xargs rm';
        }
        if (preg_match('/\bchmod\s+-r\b(?:\s+\S+)*\s+\/(?:\*+)?(?=\s|$|[;&|])/', $lower) === 1) {
            return '不允许对根路径使用 chmod -R';
        }
        if (preg_match('/\bchown\s+-r\b(?:\s+\S+)*\s+\/(?:\*+)?(?=\s|$|[;&|])/', $lower) === 1) {
            return '不允许对根路径使用 chown -R';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function argTokens(string $rest): array
    {
        $rest = trim($rest);
        if ($rest === '') {
            return [];
        }
        $parts = preg_split('/\s+/', $rest);
        if ($parts === false) {
            return [];
        }
        $tokens = [];
        foreach ($parts as $part) {
            $tokens[] = self::stripWrappingQuotes($part);
        }

        return $tokens;
    }

    /**
     * @param list<string> $tokens
     */
    private static function hasShortFlag(array $tokens, string $letter): bool
    {
        foreach ($tokens as $token) {
            if ($token === '-' . $letter) {
                return true;
            }
            if (preg_match('/^-[a-zA-Z]*' . preg_quote($letter, '/') . '[a-zA-Z]*$/', $token) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function stripWrappingQuotes(string $value): string
    {
        $value = trim($value);
        $len = strlen($value);
        if ($len < 2) {
            return $value;
        }
        $first = $value[0];
        $last = $value[$len - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
