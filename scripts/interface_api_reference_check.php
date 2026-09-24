<?php

declare(strict_types=1);

/**
 * InterfaceApi §2 引用边界检查（与 swoolefy/docs/InterfaceApi.md 一致）。
 *
 * 扫描 InterfaceApi/{project}/ 下除 Client/、Support/、vendor/ 外的 PHP 文件。
 *
 * 用法：php scripts/interface_api_reference_check.php [contractRoot]
 */

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : dirname(__DIR__) . '/InterfaceApi/ScheduleJob';

if (!is_dir($root)) {
    fwrite(STDERR, "Contract root not found: {$root}\n");
    exit(2);
}

$violations = [];
$files = listContractPhpFiles($root);

foreach ($files as $file) {
    $code = file_get_contents($file);
    if ($code === false) {
        continue;
    }
    $tokens = token_get_all($code);
    $namespace = parseNamespace($tokens);
    $uses = parseUseMap($tokens);
    $lineOf = lineMap($tokens);

    collectReferences($tokens, $namespace, $uses, $file, $lineOf, $violations);
}

if ($violations !== []) {
    foreach ($violations as [$file, $line, $fqcn]) {
        fwrite(STDERR, "{$file}:{$line} {$fqcn}\n");
    }
    fwrite(STDERR, sprintf("Reference check failed (%d violation(s)).\n", count($violations)));
    exit(1);
}

fwrite(STDOUT, sprintf("Reference check passed (%d file(s) under %s).\n", count($files), $root));
exit(0);

/** @return list<string> */
function listContractPhpFiles(string $root): array
{
    $out = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($iterator as $info) {
        if (!$info->isFile() || $info->getExtension() !== 'php') {
            continue;
        }
        $path = $info->getPathname();
        $rel = str_replace('\\', '/', substr($path, strlen(dirname($root)) + 1));
        if (preg_match('#/(Client|Support|vendor)/#', '/' . $rel)) {
            continue;
        }
        $out[] = $path;
    }
    sort($out);

    return $out;
}

/** @param list<int|string> $tokens */
function parseNamespace(array $tokens): string
{
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_NAMESPACE) {
            continue;
        }
        $parts = [];
        for ($j = $i + 1; $j < $count; $j++) {
            if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                break;
            }
            if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $parts[] = $tokens[$j][1];
            }
        }

        return trim(implode('', $parts), '\\');
    }

    return '';
}

/**
 * @param list<int|string> $tokens
 * @return array<string, string> alias => FQCN
 */
function parseUseMap(array $tokens): array
{
    $map = [];
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_USE) {
            continue;
        }
        if ($i > 0 && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] === T_FUNCTION) {
            continue;
        }
        $j = $i + 1;
        $fqcn = '';
        $alias = null;
        while ($j < $count && $tokens[$j] !== ';') {
            if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $fqcn .= $tokens[$j][1];
            }
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_AS) {
                $j++;
                if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $alias = $tokens[$j][1];
                }
            }
            $j++;
        }
        $fqcn = trim($fqcn, '\\');
        if ($fqcn === '') {
            continue;
        }
        $short = $alias ?? basename(str_replace('\\', '/', $fqcn));
        $map[$short] = $fqcn;
    }

    return $map;
}

/** @param list<int|string> $tokens @return array<int, int> */
function lineMap(array $tokens): array
{
    $map = [];
    foreach ($tokens as $idx => $tok) {
        if (is_array($tok)) {
            $map[$idx] = $tok[2];
        }
    }

    return $map;
}

/**
 * @param list<int|string> $tokens
 * @param array<string, string> $uses
 * @param list<array{0: string, 1: int, 2: string}> $violations
 */
function collectReferences(
    array $tokens,
    string $namespace,
    array $uses,
    string $file,
    array $lineOf,
    array &$violations,
): void {
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];

        if ($tok === T_NEW && isset($tokens[$i + 1])) {
            $name = readClassName($tokens, $i + 1);
            if ($name !== null) {
                report($file, $lineOf[$i + 1] ?? $lineOf[$i] ?? 1, resolveName($name, $namespace, $uses), $violations);
            }
        }

        if (is_array($tok) && $tok[0] === T_USE && ($i === 0 || !is_array($tokens[$i - 1]) || $tokens[$i - 1][0] !== T_FUNCTION)) {
            $j = $i + 1;
            $fqcn = '';
            while ($j < $count && $tokens[$j] !== ';') {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                    $fqcn .= $tokens[$j][1];
                }
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_AS) {
                    break;
                }
                $j++;
            }
            report($file, $tok[2], trim($fqcn, '\\'), $violations);
        }

        if (is_array($tok) && in_array($tok[0], [T_EXTENDS, T_IMPLEMENTS], true)) {
            $j = $i + 1;
            while ($j < $count && $tokens[$j] !== '{' && $tokens[$j] !== ';') {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                    report($file, $tokens[$j][2], resolveName($tokens[$j][1], $namespace, $uses), $violations);
                }
                $j++;
            }
        }

        if (is_array($tok) && $tok[0] === T_DOUBLE_COLON && $i > 0) {
            $name = readClassNameBefore($tokens, $i - 1);
            if ($name !== null) {
                report($file, $tok[2], resolveName($name, $namespace, $uses), $violations);
            }
        }

        if (is_array($tok) && $tok[0] === T_STRING && isset($tokens[$i + 1]) && $tokens[$i + 1] === '::'
            && isset($tokens[$i + 2]) && is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_CLASS) {
            report($file, $tok[2], resolveName($tok[1], $namespace, $uses), $violations);
        }

        if (is_array($tok) && in_array($tok[0], [T_NAME_QUALIFIED, T_NS_SEPARATOR, T_STRING], true)) {
            if (isset($tokens[$i + 1]) && is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_VARIABLE) {
                report($file, $tok[2], resolveName($tok[1], $namespace, $uses), $violations);
            }
        }
    }
}

/** @param list<int|string> $tokens */
function readClassName(array $tokens, int $start): ?string
{
    if (!isset($tokens[$start])) {
        return null;
    }
    if (is_array($tokens[$start]) && $tokens[$start][0] === T_NAME_FULLY_QUALIFIED) {
        return ltrim($tokens[$start][1], '\\');
    }
    if (is_array($tokens[$start]) && in_array($tokens[$start][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
        return $tokens[$start][1];
    }

    return null;
}

/** @param list<int|string> $tokens */
function readClassNameBefore(array $tokens, int $idx): ?string
{
    while ($idx >= 0 && $tokens[$idx] === ')') {
        $idx--;
    }
    if ($idx >= 0 && is_array($tokens[$idx]) && in_array($tokens[$idx][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR, T_NAME_FULLY_QUALIFIED], true)) {
        $name = $tokens[$idx][1];
        if ($tokens[$idx][0] === T_NAME_FULLY_QUALIFIED) {
            $name = ltrim($name, '\\');
        }

        return $name;
    }

    return null;
}

/** @param list<array{0: string, 1: int, 2: string}> $violations */
function report(string $file, int $line, string $fqcn, array &$violations): void
{
    $fqcn = trim($fqcn, '\\');
    if ($fqcn === '' || $fqcn === 'self' || $fqcn === 'static' || $fqcn === 'parent') {
        return;
    }
    if (!isAllowedFqcn($fqcn)) {
        $violations[] = [$file, $line, $fqcn];
    }
}

function resolveName(string $name, string $namespace, array $uses): string
{
    $name = ltrim($name, '\\');
    if (str_contains($name, '\\')) {
        return $name;
    }
    if (isset($uses[$name])) {
        return $uses[$name];
    }
    if ($namespace !== '') {
        return $namespace . '\\' . $name;
    }

    return $name;
}

function isAllowedFqcn(string $fqcn): bool
{
    if (str_starts_with($fqcn, 'InterfaceApi\\')) {
        return true;
    }
    if (!str_contains($fqcn, '\\')) {
        if (class_exists($fqcn, false) || interface_exists($fqcn, false) || trait_exists($fqcn, false) || enum_exists($fqcn, false)) {
            $ref = new ReflectionClass($fqcn);

            return $ref->getFileName() === false;
        }

        return true;
    }

    return false;
}
