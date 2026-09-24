<?php

declare(strict_types=1);

/**
 * InterfaceApi §2 引用边界检查。
 *
 * 用法：php scripts/interface_api_reference_check.php [contractRoot]
 */

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : dirname(__DIR__) . '/InterfaceApi/ScheduleJob';

require dirname(__DIR__) . '/App/Autoloader.php';
\App\Autoloader::register();

use InterfaceApi\Support\Generator\ReferenceChecker;

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
