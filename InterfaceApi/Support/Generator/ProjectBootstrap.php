<?php

declare(strict_types=1);

namespace InterfaceApi\Support\Generator;

/**
 * generate-client / generate-openapi 仅在 interface-api-service 仓库根内运行时的引导与自动加载。
 */
final class ProjectBootstrap
{
    private static bool $registered = false;

    private static ?string $repositoryRoot = null;

    public static function resolveRepositoryRootFromBinDir(string $binDir): string
    {
        $root = realpath(dirname($binDir)) ?: dirname($binDir);
        $support = $root . DIRECTORY_SEPARATOR . 'Support';
        if (!is_dir($support)) {
            throw new GeneratorException('Not an interface-api-service tree (missing Support/): ' . $root);
        }

        return $root;
    }

    public static function repositoryRoot(): string
    {
        if (self::$repositoryRoot === null) {
            throw new GeneratorException('ProjectBootstrap::register() has not been called');
        }

        return self::$repositoryRoot;
    }

    /** 契约包根目录（与本仓库根相同）。 */
    public static function resolveInterfaceApiRoot(): string
    {
        return self::repositoryRoot();
    }

    public static function register(string $repositoryRoot): void
    {
        if (self::$registered) {
            return;
        }

        if (!is_dir($repositoryRoot . DIRECTORY_SEPARATOR . 'Support')) {
            throw new GeneratorException('Not an interface-api-service tree: ' . $repositoryRoot);
        }

        self::$repositoryRoot = realpath($repositoryRoot) ?: $repositoryRoot;

        self::registerInterfaceApiAutoload(self::$repositoryRoot);

        $vendor = self::resolveVendorAutoload(self::$repositoryRoot);
        if ($vendor === null) {
            throw new GeneratorException(
                'Missing vendor/autoload.php. Run `composer install` in the contract repo'
                . ' (interface-api-service), or in the host project that contains InterfaceApi/ (schedule-job).',
            );
        }
        require_once $vendor;

        self::$registered = true;
    }

    /**
     * 独立契约仓：{仓库根}/vendor/autoload.php。
     * 嵌在业务项目里：{项目根}/vendor/autoload.php（InterfaceApi 的上一级）。
     */
    private static function resolveVendorAutoload(string $repositoryRoot): ?string
    {
        $candidates = [
            $repositoryRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
            dirname($repositoryRoot) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function registerInterfaceApiAutoload(string $repositoryRoot): void
    {
        spl_autoload_register(static function (string $class) use ($repositoryRoot): void {
            if ($class !== 'InterfaceApi' && !str_starts_with($class, 'InterfaceApi\\')) {
                return;
            }
            $relative = substr($class, strlen('InterfaceApi\\'));
            $path = $repositoryRoot . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        });
    }
}
