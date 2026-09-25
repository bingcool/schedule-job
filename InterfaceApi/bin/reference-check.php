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
require_once $support . '/ConsoleReporter.php';

use InterfaceApi\Support\Generator\ConsoleReporter;
use InterfaceApi\Support\Generator\ReferenceChecker;

$defaultRoot = $interfaceApiRoot . DIRECTORY_SEPARATOR . 'ScheduleJob';
$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : $defaultRoot;

$projectRoot = dirname($interfaceApiRoot);
if (!is_dir($projectRoot . DIRECTORY_SEPARATOR . 'App')) {
    $projectRoot = $interfaceApiRoot;
}

$console = new ConsoleReporter($projectRoot);
$started = microtime(true);

$console->banner('InterfaceApi Reference Check (§2)');
$console->meta('contract', $console->relPath($root));
$console->info('only InterfaceApi\\ references allowed in contract PHP');

if (!is_dir($root)) {
    $console->error('contract root not found: ' . $root);
    $console->finishFailure(microtime(true) - $started, 'invalid path');
    exit(2);
}

$console->section('Scan (excludes Client/, Support/, vendor/)');
$violations = (new ReferenceChecker())->check($root);

if ($violations !== []) {
    $console->section('Violations');
    foreach ($violations as [$file, $line, $fqcn]) {
        $console->error($console->relPath($file) . ':' . $line . '  ' . $fqcn);
    }
    $count = count($violations);
    $console->finishFailure(
        microtime(true) - $started,
        $count . ' forbidden reference(s)',
    );
    exit(1);
}

$console->ok('boundary check passed');
$console->finishSuccess(microtime(true) - $started, '0 violation(s)');

exit(0);
