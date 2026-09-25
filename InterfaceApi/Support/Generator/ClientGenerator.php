<?php

declare(strict_types=1);

namespace InterfaceApi\Support\Generator;

use InterfaceApi\Support\RouteGroup;

/**
 * 从 InterfaceApi/{route}/ 契约生成 Client（InterfaceApi.md §2 + §5）。
 */
final class ClientGenerator
{
    public function __construct(
        private string $projectRoot,
        private string $serviceKey,
        private ?ConsoleReporter $console = null,
    ) {
    }

    public function run(): void
    {
        $started = microtime(true);
        $console = $this->console ?? new ConsoleReporter($this->projectRoot);

        [$appRoot, $moduleRoot, $packageRoot] = $this->resolveServicePaths();

        $this->bootstrapAutoload();

        $serviceName = $this->resolveServiceName($packageRoot);

        $console->banner();
        $console->meta('service', $this->serviceKey);
        $console->meta('serviceName', $serviceName);
        $console->meta('scan', $console->relPath($moduleRoot));

        $console->section('Reference check (§2)');
        $violations = (new ReferenceChecker())->check($appRoot);
        if ($violations !== []) {
            foreach ($violations as [$file, $line, $fqcn]) {
                $console->error("{$console->relPath($file)}:{$line} {$fqcn}");
            }
            throw new GeneratorException(sprintf('Reference check failed (%d violation(s)).', count($violations)));
        }
        $console->ok('passed');

        $interfaces = $this->discoverRouteGroupInterfaces($moduleRoot);
        if ($interfaces === []) {
            throw new GeneratorException('No #[RouteGroup] interfaces found under ' . $moduleRoot);
        }

        $console->section('Discover #[RouteGroup] interfaces');
        $console->info(sprintf('found %d interface(s)', count($interfaces)));

        $writer = new ClientWriter();
        $written = [];
        $skipped = 0;
        $console->section('Write Client classes');
        foreach ($interfaces as $fqcn) {
            $ifaceShort = $console->shortInterfaceName($fqcn);
            if (!$writer->hasApiMethods($fqcn)) {
                ++$skipped;
                $console->skip("{$ifaceShort} — no API methods");
                continue;
            }
            $path = $writer->write($fqcn, $serviceName);
            $written[] = $path;
            $clientShort = preg_replace('/ApiInterface$/', 'Api', $ifaceShort) ?: ($ifaceShort . 'Api');
            $console->ok("{$ifaceShort} → {$clientShort}", $console->relPath($path));
        }
        if ($written === []) {
            throw new GeneratorException('No Client generated (interfaces have no routable methods)');
        }

        ClientDirectoryReadme::syncForWrittenClients($written);

        $removed = $this->purgeStaleGeneratedClients($moduleRoot, $written);
        if ($removed > 0) {
            $console->section('Cleanup stale @generated files');
            $console->info("removed {$removed} file(s)");
        }

        $console->summary(microtime(true) - $started, count($written), $skipped, $removed);
    }

    /**
     * @return array{0: string, 1: string, 2: string} appRoot, moduleRoot, packageRoot (e.g. InterfaceApi/ScheduleJob)
     */
    private function resolveServicePaths(): array
    {
        $serviceKey = trim(str_replace('\\', '/', $this->serviceKey), '/');
        if ($serviceKey === '') {
            throw new GeneratorException('Empty --service value');
        }
        if (!str_ends_with($serviceKey, '/App') && $serviceKey !== 'App') {
            throw new GeneratorException(
                '--service must end with /App (e.g. ScheduleJob/App), got: ' . $this->serviceKey,
            );
        }

        $interfaceApiRoot = ProjectBootstrap::resolveInterfaceApiRoot($this->projectRoot);
        $appRoot = $interfaceApiRoot
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $serviceKey);
        if (!is_dir($appRoot)) {
            throw new GeneratorException("App directory not found: {$appRoot}");
        }
        if (basename($appRoot) !== 'App') {
            throw new GeneratorException("Expected App directory, got: {$appRoot}");
        }

        $moduleRoot = $appRoot . DIRECTORY_SEPARATOR . 'Module';
        if (!is_dir($moduleRoot)) {
            throw new GeneratorException("Module directory not found: {$moduleRoot}");
        }

        $packageRoot = dirname($appRoot);

        return [$appRoot, $moduleRoot, $packageRoot];
    }

    private function bootstrapAutoload(): void
    {
        ProjectBootstrap::register($this->projectRoot);
    }

    private function resolveServiceName(string $packageRoot): string
    {
        $jsonPath = $packageRoot . DIRECTORY_SEPARATOR . 'interface-api.json';
        if (!is_file($jsonPath)) {
            throw new GeneratorException(
                "Missing {$jsonPath} (need serviceName per InterfaceApi.md §5.4)",
            );
        }
        $data = json_decode((string) file_get_contents($jsonPath), true);
        if (!is_array($data)) {
            throw new GeneratorException('Invalid JSON: ' . $jsonPath);
        }

        $serviceKey = trim(str_replace('\\', '/', $this->serviceKey), '/');
        $cfg = $data[$serviceKey] ?? null;
        if (is_array($cfg) && isset($cfg['serviceName']) && is_string($cfg['serviceName']) && $cfg['serviceName'] !== '') {
            return $cfg['serviceName'];
        }

        throw new GeneratorException(
            "interface-api.json must define serviceName for key \"{$serviceKey}\" in {$jsonPath}",
        );
    }

    /**
     * @return list<string> FQCN
     */
    private function discoverRouteGroupInterfaces(string $scanRoot): array
    {
        $out = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($scanRoot));
        foreach ($iterator as $info) {
            if (!$info->isFile() || $info->getExtension() !== 'php') {
                continue;
            }
            $path = $info->getPathname();
            if (str_contains(str_replace('\\', '/', $path), '/Client/')) {
                continue;
            }
            if (str_contains(str_replace('\\', '/', $path), '/Support/')) {
                continue;
            }
            $code = file_get_contents($path);
            if ($code === false || !preg_match('/\binterface\s+(\w+)/', $code, $m)) {
                continue;
            }
            if (!preg_match('/namespace\s+([^;]+);/', $code, $ns)) {
                continue;
            }
            $fqcn = trim($ns[1]) . '\\' . $m[1];
            if (!interface_exists($fqcn)) {
                continue;
            }
            $ref = new \ReflectionClass($fqcn);
            if ($ref->getAttributes(RouteGroup::class) !== []) {
                $out[] = $fqcn;
            }
        }
        sort($out);

        return $out;
    }

    /**
     * @param list<string> $writtenPaths
     */
    private function purgeStaleGeneratedClients(string $scanRoot, array $writtenPaths): int
    {
        $removed = 0;
        $keep = array_map(static fn (string $p): string => realpath($p) ?: $p, $writtenPaths);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($scanRoot));
        foreach ($iterator as $info) {
            if (!$info->isFile() || $info->getExtension() !== 'php') {
                continue;
            }
            $path = $info->getPathname();
            if (!str_contains(str_replace('\\', '/', $path), '/Client/')) {
                continue;
            }
            $head = file_get_contents($path, false, null, 0, 128);
            if ($head === false || !str_contains($head, '@generated')) {
                continue;
            }
            $real = realpath($path) ?: $path;
            if (!in_array($real, $keep, true)) {
                unlink($path);
                ++$removed;
            }
        }

        $this->removeEmptyLegacyInterfaceClientDirs($scanRoot);

        return $removed;
    }

    private function removeEmptyLegacyInterfaceClientDirs(string $scanRoot): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scanRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $info) {
            if (!$info->isDir()) {
                continue;
            }
            $path = $info->getPathname();
            $normalized = str_replace('\\', '/', $path);
            if (!preg_match('#/Interface/Client$#', $normalized)) {
                continue;
            }
            if ($this->isDirEmpty($path)) {
                @rmdir($path);
            }
        }
    }

    private function isDirEmpty(string $dir): bool
    {
        $handle = opendir($dir);
        if ($handle === false) {
            return false;
        }
        while (($entry = readdir($handle)) !== false) {
            if ($entry !== '.' && $entry !== '..') {
                closedir($handle);

                return false;
            }
        }
        closedir($handle);

        return true;
    }
}
