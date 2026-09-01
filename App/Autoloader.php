<?php
/**
 * +----------------------------------------------------------------------
 * | swoolefy framework bases on swoole extension development, we can use it easily!
 * +----------------------------------------------------------------------
 * | Licensed ( https://opensource.org/licenses/MIT )
 * +----------------------------------------------------------------------
 * | @see https://github.com/bingcool/swoolefy
 * +----------------------------------------------------------------------
 */

/**
 * 应用命名空间自动加载模板（create 时复制到 APP_PATH/Autoloader.php，并替换 "App"）。
 *
 * 类名：\{AppName}\Autoloader（与业务根命名空间一致，避免多应用全局 class Autoloader 冲突）
 * 路径：{START_DIR_ROOT}/{AppName}/...
 * 可被多次 include。
 *
 * 注意：本文件仅作模板；占位符 App 在复制到应用目录时替换为真实应用名。
 */
namespace App;

if (!class_exists(__NAMESPACE__ . '\\Autoloader', false)) {
    class Autoloader
    {
        /** @var string|null */
        private static $baseDirectory = null;

        /** @var list<string> */
        private static $rootNamespace = ['App'];

        /** @var array<string, true> */
        private static $classMapNamespace = [];

        /** @var array<string, int> className => coroutine id currently requiring the file */
        private static $loading = [];

        /** @var bool */
        private static $registered = false;

        /**
         * @param string $className
         */
        public static function autoload($className): void
        {
            if (self::isDefined($className)) {
                self::$classMapNamespace[$className] = true;
                return;
            }

            $cid = self::coroutineId();
            if ($cid >= 0 && isset(self::$loading[$className])) {
                if (self::$loading[$className] === $cid) {
                    return;
                }
                self::waitUntilLoaded($className);
                return;
            }

            if ($cid >= 0) {
                self::$loading[$className] = $cid;
            }

            try {
                self::loadFromFile($className);
                if (self::isDefined($className)) {
                    self::$classMapNamespace[$className] = true;
                }
            } finally {
                unset(self::$loading[$className]);
            }
        }

        /**
         * Worker 接请求前只预加载 Model / Entity。
         * Controller、Service、DTO 仍懒加载；协程竞态由 autoload 锁处理。
         * 全量 require App/ 会随业务文件线性变慢，且每个 Worker 都要付一遍。
         */
        public static function preloadAppClasses(): void
        {
            $appPath = defined('APP_PATH')
                ? APP_PATH
                : (self::baseDirectory() . DIRECTORY_SEPARATOR . 'App');
            if (!is_dir($appPath)) {
                return;
            }

            $files = self::collectPreloadPhpFiles($appPath);
            sort($files, SORT_STRING);

            foreach ($files as $filepath) {
                require_once $filepath;
                $className = self::classNameFromFile($filepath);
                if ($className !== null && self::isDefined($className)) {
                    self::$classMapNamespace[$className] = true;
                }
            }
        }

        public static function register($prepend = false): void
        {
            if (self::$registered) {
                return;
            }
            self::$registered = true;

            if (!function_exists('__autoload')) {
                spl_autoload_register([self::class, 'autoload'], true, $prepend);
            } else {
                trigger_error(
                    'spl_autoload_register() which will bypass your __autoload() and may break your autoloading',
                    E_USER_WARNING,
                );
            }
        }

        private static function loadFromFile(string $className): void
        {
            foreach (self::$rootNamespace as $namespace) {
                // 精确前缀：Foo 不匹配 Foobar\
                if ($className !== $namespace && !str_starts_with($className, $namespace . '\\')) {
                    continue;
                }

                $parts = explode('\\', $className);
                $filepath = self::baseDirectory()
                    . DIRECTORY_SEPARATOR
                    . implode(DIRECTORY_SEPARATOR, $parts)
                    . '.php';

                if (!is_file($filepath)) {
                    clearstatcache(true, $filepath);
                }

                if (is_file($filepath)) {
                    require_once $filepath;
                }

                break;
            }
        }

        private static function waitUntilLoaded(string $className): void
        {
            $spins = 0;
            while (isset(self::$loading[$className])) {
                if (self::isDefined($className)) {
                    self::$classMapNamespace[$className] = true;
                    return;
                }
                if (++$spins > 2000) {
                    break;
                }
                \Swoole\Coroutine::sleep(0.001);
            }

            if (self::isDefined($className)) {
                self::$classMapNamespace[$className] = true;
                return;
            }

            self::loadFromFile($className);
            if (self::isDefined($className)) {
                self::$classMapNamespace[$className] = true;
            }
        }

        private static function isDefined(string $name): bool
        {
            return class_exists($name, false)
                || interface_exists($name, false)
                || trait_exists($name, false)
                || enum_exists($name, false);
        }

        private static function coroutineId(): int
        {
            if (!extension_loaded('swoole')) {
                return -1;
            }

            return (int) \Swoole\Coroutine::getCid();
        }

        private static function baseDirectory(): string
        {
            if (self::$baseDirectory === null) {
                self::$baseDirectory = defined('START_DIR_ROOT')
                    ? START_DIR_ROOT
                    : dirname(__DIR__);
            }

            return self::$baseDirectory;
        }

        /**
         * 只收集 App/Model 与各模块 Entity 目录，避免扫整棵业务树。
         *
         * @return list<string>
         */
        private static function collectPreloadPhpFiles(string $appPath): array
        {
            $files = [];
            $modelDir = $appPath . DIRECTORY_SEPARATOR . 'Model';
            if (is_dir($modelDir)) {
                $files = array_merge($files, self::collectPhpFilesIn($modelDir));
            }

            $moduleDir = $appPath . DIRECTORY_SEPARATOR . 'Module';
            if (!is_dir($moduleDir)) {
                return $files;
            }

            $modules = scandir($moduleDir);
            if ($modules === false) {
                return $files;
            }

            foreach ($modules as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $entityDir = $moduleDir . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'Entity';
                if (is_dir($entityDir)) {
                    $files = array_merge($files, self::collectPhpFilesIn($entityDir));
                }
            }

            return $files;
        }

        /**
         * @return list<string>
         */
        private static function collectPhpFilesIn(string $dir): array
        {
            $files = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $dir,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO
                )
            );

            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo
                    && $file->isFile()
                    && strtolower($file->getExtension()) === 'php'
                ) {
                    $files[] = $file->getPathname();
                }
            }

            return $files;
        }

        private static function classNameFromFile(string $filepath): ?string
        {
            $base = self::baseDirectory();
            $normalizedBase = rtrim(str_replace('\\', '/', $base), '/') . '/';
            $normalizedFile = str_replace('\\', '/', $filepath);
            if (!str_starts_with($normalizedFile, $normalizedBase)) {
                return null;
            }

            $relative = substr($normalizedFile, strlen($normalizedBase));
            if (!str_ends_with($relative, '.php')) {
                return null;
            }

            return str_replace('/', '\\', substr($relative, 0, -4));
        }
    }

    Autoloader::register();
}

// cli.php 定义 APP_PATH 后再 include 本文件时加载业务常量
if (defined('APP_PATH') && is_file(APP_PATH . '/Config/constants.php')) {
    include_once APP_PATH . '/Config/constants.php';
}
