<?php

declare(strict_types=1);

namespace InterfaceApi\Support\Generator;

/**
 * generate-client 与 ClientGenerator 共用的仓库根目录解析与自动加载。
 */
final class ProjectBootstrap
{
    private static bool $registered = false;

    /**
     * @return array{0: string, 1: string} projectRoot, interfaceApiRoot
     */
    public static function resolveFromBinDir(string $binDir): array
    {
        $interfaceApiRoot = dirname($binDir);
        $support = $interfaceApiRoot . DIRECTORY_SEPARATOR . 'Support';
        if (!is_dir($support)) {
            throw new GeneratorException('Not an InterfaceApi tree (missing Support/): ' . $interfaceApiRoot);
        }

        $projectRoot = dirname($interfaceApiRoot);
        $nested = $projectRoot . DIRECTORY_SEPARATOR . 'InterfaceApi';
        if (!is_dir($nested)) {
            throw new GeneratorException(
                'Project root must contain InterfaceApi/ (run from schedule-job repo): ' . $projectRoot,
            );
        }

        return [$projectRoot, realpath($interfaceApiRoot) ?: $interfaceApiRoot];
    }

    public static function register(string $projectRoot, string $interfaceApiRoot): void
    {
        if (self::$registered) {
            return;
        }

        $composer = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (is_file($composer)) {
            require_once $composer;
        } else {
            $appAutoload = $projectRoot . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Autoloader.php';
            if (is_file($appAutoload)) {
                require_once $appAutoload;
                if (class_exists(\App\Autoloader::class, false)) {
                    \App\Autoloader::register();
                    self::$registered = true;

                    return;
                }
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
}
