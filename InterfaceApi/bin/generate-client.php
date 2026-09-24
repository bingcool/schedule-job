#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 生成 InterfaceApi HTTP Client（契约包内生成器，不依赖 swoolefy gen:sdk）。
 * 每个带 #[RouteGroup] 且含 API 方法的 Interface 生成一个 Client（XxxApiInterface → XxxApi）。
 *
 * 用法（在 schedule-job 仓库根目录）：
 *   php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App
 */

$binDir = __DIR__;
$interfaceApiRoot = dirname($binDir);

require_once $interfaceApiRoot . '/Support/Generator/GeneratorException.php';
require_once $interfaceApiRoot . '/Support/Generator/ProjectBootstrap.php';

use InterfaceApi\Support\Generator\ClientGenerator;
use InterfaceApi\Support\Generator\GeneratorException;
use InterfaceApi\Support\Generator\ProjectBootstrap;

try {
    [$projectRoot] = ProjectBootstrap::resolveFromBinDir($binDir);
} catch (GeneratorException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(2);
}

ProjectBootstrap::register($projectRoot, $interfaceApiRoot);

$serviceKey = parseGenerateClientArgv($argv ?? []);

try {
    (new ClientGenerator($projectRoot, $serviceKey))->run();
} catch (GeneratorException $e) {
    fwrite(STDERR, PHP_EOL . 'Error: ' . $e->getMessage() . PHP_EOL . PHP_EOL);
    exit(1);
}

function parseGenerateClientArgv(array $argv): string
{
    $serviceKey = null;

    foreach ($argv as $i => $arg) {
        if ($i === 0) {
            continue;
        }
        if (str_starts_with($arg, '--service=')) {
            $serviceKey = substr($arg, strlen('--service='));
        }
    }

    if ($serviceKey === null || trim($serviceKey) === '') {
        fwrite(STDERR, "Missing required --service=ScheduleJob/App\n");
        fwrite(STDERR, "Usage: php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App\n");
        exit(2);
    }

    return trim($serviceKey);
}
