#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 从 InterfaceApi 契约（*ApiInterface + #[Route]）生成 OpenAPI 3.0 YAML。
 * 行为对齐 swoolefy `gen:apidoc` 的 schema/响应信封规则，数据源改为契约接口而非 Router。
 *
 * 用法（schedule-job 仓库根）：
 *   php InterfaceApi/bin/generate-openapi.php --service=ScheduleJob/App
 *   php InterfaceApi/bin/generate-openapi.php --service=ScheduleJob/App --out=swaggerui/apidoc
 */

$binDir = __DIR__;
$interfaceApiRoot = dirname($binDir);

require_once $interfaceApiRoot . '/Support/Generator/GeneratorException.php';
require_once $interfaceApiRoot . '/Support/Generator/ProjectBootstrap.php';

use InterfaceApi\Support\Generator\GeneratorException;
use InterfaceApi\Support\Generator\OpenApiDocGenerator;
use InterfaceApi\Support\Generator\ProjectBootstrap;

try {
    [$projectRoot] = ProjectBootstrap::resolveFromBinDir($binDir);
} catch (GeneratorException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(2);
}

ProjectBootstrap::register($projectRoot, $interfaceApiRoot);

[$serviceKey, $outRel] = parseGenerateOpenApiArgv($argv ?? []);

$interfaceApiRoot = ProjectBootstrap::resolveInterfaceApiRoot($projectRoot);
$outputDir = $outRel !== ''
    ? $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($outRel, '/\\'))
    : $interfaceApiRoot . DIRECTORY_SEPARATOR . explode('/', $serviceKey)[0] . DIRECTORY_SEPARATOR . 'openapi';

try {
    (new OpenApiDocGenerator($projectRoot, $serviceKey, $outputDir))->run();
} catch (GeneratorException $e) {
    fwrite(STDERR, PHP_EOL . 'Error: ' . $e->getMessage() . PHP_EOL . PHP_EOL);
    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, PHP_EOL . 'Error: ' . $e->getMessage() . PHP_EOL . PHP_EOL);
    exit(1);
}

/**
 * @return array{0: string, 1: string} serviceKey, outRel
 */
function parseGenerateOpenApiArgv(array $argv): array
{
    $serviceKey = null;
    $outRel = '';

    foreach ($argv as $i => $arg) {
        if ($i === 0) {
            continue;
        }
        if (str_starts_with($arg, '--service=')) {
            $serviceKey = substr($arg, strlen('--service='));
        }
        if (str_starts_with($arg, '--out=')) {
            $outRel = substr($arg, strlen('--out='));
        }
    }

    if ($serviceKey === null || trim($serviceKey) === '') {
        fwrite(STDERR, "Missing required --service=ScheduleJob/App\n");
        fwrite(STDERR, "Usage: php InterfaceApi/bin/generate-openapi.php --service=ScheduleJob/App [--out=InterfaceApi/ScheduleJob/openapi]\n");
        exit(2);
    }

    return [trim($serviceKey), trim($outRel)];
}
