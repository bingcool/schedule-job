<?php

declare(strict_types=1);

namespace InterfaceApi\Support\Generator;

/**
 * generate-client 与 ClientGenerator 共用的仓库根目录解析与自动加载。
 */
final class ProjectBootstrap
{
    private static bool $registered = false;

    public static function resolveInterfaceApiRoot(string $projectRoot): string
    {
        $autoload = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Autoloader.php';
        if (is_file($autoload)) {
            require_once $autoload;
            if (method_exists(\App\Autoloader::class, 'interfaceApiRoot')) {
                return \App\Autoloader::interfaceApiRoot($projectRoot);
            }
        }

        return self::fallbackInterfaceApiRoot($projectRoot);
    }

    /**
     * @return array{0: string, 1: string} projectRoot, interfaceApiRoot
     */
    public static function resolveFromBinDir(string $binDir): array
    {
        $interfaceApiRoot = realpath(dirname($binDir)) ?: dirname($binDir);
        $support = $interfaceApiRoot . DIRECTORY_SEPARATOR . 'Support';
        if (!is_dir($support)) {
            throw new GeneratorException('Not an InterfaceApi tree (missing Support/): ' . $interfaceApiRoot);
        }

        $projectRoot = self::findApplicationProjectRoot($interfaceApiRoot);

        return [$projectRoot, $interfaceApiRoot];
    }

    public static function register(string $projectRoot, ?string $interfaceApiRoot = null): void
    {
        if (self::$registered) {
            return;
        }

        $interfaceApiRoot ??= self::resolveInterfaceApiRoot($projectRoot);

        $composer = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (is_file($composer)) {
            require_once $composer;
        }

        $appAutoload = $projectRoot . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Autoloader.php';
        if (is_file($appAutoload)) {
            require_once $appAutoload;
            if (class_exists(\App\Autoloader::class, false)) {
                $prepend = \App\Autoloader::isRegisterLocalInterfaceApi();
                \App\Autoloader::register($prepend);
                self::$registered = true;

                return;
            }
        }

        spl_autoload_register(static function (string $class) use ($interfaceApiRoot): void {
            if ($class !== 'InterfaceApi' && !str_starts_with($class, 'InterfaceApi\\')) {
                return;
            }
            $relative = substr($class, strlen('InterfaceApi\\'));
            $path = $interfaceApiRoot . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        });

        self::$registered = true;
    }

    private static function findApplicationProjectRoot(string $interfaceApiRoot): string
    {
        $parent = dirname($interfaceApiRoot);

        if (self::looksLikeScheduleJobRoot($parent)) {
            return realpath($parent) ?: $parent;
        }

        if (is_dir($parent)) {
            foreach (scandir($parent) ?: [] as $name) {
                if ($name === '.' || $name === '..' || $name === basename($interfaceApiRoot)) {
                    continue;
                }
                $candidate = $parent . DIRECTORY_SEPARATOR . $name;
                if (self::looksLikeScheduleJobRoot($candidate)) {
                    return realpath($candidate) ?: $candidate;
                }
            }
        }

        throw new GeneratorException(
            'Cannot find schedule-job project root (need App/ + cli.php sibling to InterfaceApi): ' . $parent,
        );
    }

    private static function looksLikeScheduleJobRoot(string $path): bool
    {
        return is_dir($path . DIRECTORY_SEPARATOR . 'App')
            && (is_file($path . DIRECTORY_SEPARATOR . 'cli.php') || is_file($path . DIRECTORY_SEPARATOR . 'cron.php'));
    }

    private static function fallbackInterfaceApiRoot(string $projectRoot): string
    {
        $embedded = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'InterfaceApi';

        return $embedded;
    }
}
