#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 生成 InterfaceApi HTTP Client（契约包内生成器，不依赖 swoolefy gen:sdk）。
 * 每个带 #[RouteGroup] 且含 API 方法的 Interface 生成一个 Client（XxxApiInterface → XxxApi）。
 *
 * 用法（在 interface-api-service 仓库根目录）：
 *   php bin/generate-client.php --service=ScheduleJob/App
 */

$binDir = __DIR__;
$repositoryRoot = dirname($binDir);

require_once $repositoryRoot . '/Support/Generator/GeneratorException.php';
require_once $repositoryRoot . '/Support/Generator/ProjectBootstrap.php';

use InterfaceApi\Support\Generator\ClientGenerator;
use InterfaceApi\Support\Generator\GeneratorException;
use InterfaceApi\Support\Generator\ProjectBootstrap;

try {
    $repositoryRoot = ProjectBootstrap::resolveRepositoryRootFromBinDir($binDir);
    ProjectBootstrap::register($repositoryRoot);
} catch (GeneratorException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(2);
}

$serviceKey = parseGenerateClientArgv($argv ?? []);

try {
    (new ClientGenerator($repositoryRoot, $serviceKey))->run();
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
        fwrite(STDERR, "Missing required --service=xxxxxx/App\n");
        fwrite(STDERR, "Usage: php bin/generate-client.php --service=xxxxxx/App\n");
        exit(2);
    }

    return trim($serviceKey);
}
