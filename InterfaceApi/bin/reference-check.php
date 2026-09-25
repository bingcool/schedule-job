#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * InterfaceApi §2 引用边界检查。
 *
 * 用法（schedule-job 根或 InterfaceApi 仓库）：
 *   php InterfaceApi/bin/reference-check.php
 *   php InterfaceApi/bin/reference-check.php InterfaceApi/ScheduleJob
 */

$interfaceApiRoot = dirname(__DIR__);
$support = $interfaceApiRoot . '/Support/Generator';

require_once $support . '/GeneratorException.php';
require_once $support . '/ReferenceChecker.php';

use InterfaceApi\Support\Generator\ReferenceChecker;

$defaultRoot = $interfaceApiRoot . DIRECTORY_SEPARATOR . 'ScheduleJob';
$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : $defaultRoot;

if (!is_dir($root)) {
    fwrite(STDERR, "Contract root not found: {$root}\n");
    exit(2);
}

$violations = (new ReferenceChecker())->check($root);

if ($violations !== []) {
    foreach ($violations as [$file, $line, $fqcn]) {
        fwrite(STDERR, "{$file}:{$line} {$fqcn}\n");
    }
    fwrite(STDERR, sprintf("Reference check failed (%d violation(s)).\n", count($violations)));
    exit(1);
}

fwrite(STDOUT, "Reference check passed (under {$root}).\n");
exit(0);
