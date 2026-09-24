#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 生成 InterfaceApi HTTP Client（委托 swoolefy gen:sdk --route=ScheduleJob）。
 *
 * 用法：
 *   php bin/generate-client.php
 *   php bin/generate-client.php --all
 */

$projectRoot = dirname(__DIR__);
$clientsMode = in_array('--all', $argv ?? [], true) ? 'all' : 'module';

require $projectRoot . '/vendor/autoload.php';

if (!class_exists(\Swoolefy\Script\InterfaceApi\InterfaceApiClientGenerator::class)) {
    fwrite(STDERR, "swoolefy dev build required (InterfaceApiClientGenerator missing).\n");
    exit(2);
}

require $projectRoot . '/App/Autoloader.php';
\App\Autoloader::register();

use Swoolefy\Script\InterfaceApi\InterfaceApiClientGenerator;
use Swoolefy\Script\InterfaceApi\InterfaceApiGeneratorException;
use Symfony\Component\Console\Output\StreamOutput;

try {
    (new InterfaceApiClientGenerator(
        $projectRoot,
        'ScheduleJob',
        new StreamOutput(STDOUT),
        $clientsMode,
    ))->run();
} catch (InterfaceApiGeneratorException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
