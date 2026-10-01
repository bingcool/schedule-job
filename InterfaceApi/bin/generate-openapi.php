#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 从 InterfaceApi 契约（*ApiInterface + #[Route]）生成 OpenAPI 3.0 YAML。
 * 行为对齐 swoolefy `gen:apidoc` 的 schema/响应信封规则，数据源改为契约接口而非 Router。
 *
 * 用法（interface-api-service 仓库根目录）：
 *   php bin/generate-openapi.php --service=ScheduleJob/App
 *   php bin/generate-openapi.php --service=ScheduleJob/App --out=ScheduleJob/openapi
 */

$binDir = __DIR__;
$repositoryRoot = dirname($binDir);

require_once $repositoryRoot . '/Support/Generator/GeneratorException.php';
require_once $repositoryRoot . '/Support/Generator/ProjectBootstrap.php';

use InterfaceApi\Support\Generator\GeneratorException;
use InterfaceApi\Support\Generator\OpenApiDocGenerator;
use InterfaceApi\Support\Generator\ProjectBootstrap;

try {
    $repositoryRoot = ProjectBootstrap::resolveRepositoryRootFromBinDir($binDir);
    ProjectBootstrap::register($repositoryRoot);
} catch (GeneratorException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(2);
}

[$serviceKey, $outRel] = parseGenerateOpenApiArgv($argv ?? []);

$outputDir = $outRel !== ''
    ? $repositoryRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($outRel, '/\\'))
    : $repositoryRoot . DIRECTORY_SEPARATOR . explode('/', $serviceKey)[0] . DIRECTORY_SEPARATOR . 'openapi';

try {
    (new OpenApiDocGenerator($repositoryRoot, $serviceKey, $outputDir))->run();
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
        fwrite(STDERR, "Missing required --service=xxxxxx/App\n");
        fwrite(STDERR, "Usage: php bin/generate-openapi.php --service=xxxxxx/App [--out=ScheduleJob/openapi]\n");
        exit(2);
    }

    return [trim($serviceKey), trim($outRel)];
}
