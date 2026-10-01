<?php

declare(strict_types=1);

namespace InterfaceApi\Support\Generator;

use InterfaceApi\Support\ApiOperation;
use InterfaceApi\Support\BaseRequest;
use InterfaceApi\Support\BaseResponse;
use InterfaceApi\Support\ChunkedResponse;
use InterfaceApi\Support\DownloadResponse;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;
use InterfaceApi\Support\StreamResponse;
use ReflectionNamedType;
use ReflectionClass;
use ReflectionMethod;

final class ClientWriter
{
    private const ALLOWED_VERBS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    private const QUERY_VERBS = ['GET', 'HEAD', 'DELETE', 'OPTIONS'];

    public function hasApiMethods(string $interfaceFqcn): bool
    {
        if (!interface_exists($interfaceFqcn)) {
            return false;
        }

        return $this->collectPublicApiMethods(new ReflectionClass($interfaceFqcn)) !== [];
    }

    /**
     * @throws GeneratorException
     */
    public function write(string $interfaceFqcn, string $serviceName): string
    {
        if (!interface_exists($interfaceFqcn)) {
            throw new GeneratorException("Interface not found: {$interfaceFqcn}");
        }

        $iface = new ReflectionClass($interfaceFqcn);
        $routeGroups = $iface->getAttributes(RouteGroup::class);
        if ($routeGroups === []) {
            throw new GeneratorException("Missing #[RouteGroup] on {$interfaceFqcn}");
        }
        /** @var RouteGroup $group */
        $group = $routeGroups[0]->newInstance();
        $prefix = '/' . trim($group->prefix, '/');

        $clientShort = preg_replace('/ApiInterface$/', 'Api', $iface->getShortName()) ?: ($iface->getShortName() . 'Api');
        [$clientDir, $clientNs] = $this->resolveClientLocation($iface);
        if (!is_dir($clientDir) && !mkdir($clientDir, 0775, true) && !is_dir($clientDir)) {
            throw new GeneratorException("Cannot create Client directory: {$clientDir}");
        }
        $clientPath = $clientDir . DIRECTORY_SEPARATOR . $clientShort . '.php';

        $methods = $this->collectPublicApiMethods($iface);
        $seenRoutes = [];
        $methodBlocks = [];
        $uses = [
            'InterfaceApi\\Support\\BaseClientApi',
        ];
        $needsCovertProperty = false;

        foreach ($methods as $method) {
            $this->validateMethod($method, $interfaceFqcn);
            $routes = $method->getAttributes(Route::class);
            if (count($routes) !== 1) {
                throw new GeneratorException(sprintf(
                    '%s:%d method %s() must have exactly one #[Route]',
                    $method->getFileName(),
                    $method->getStartLine(),
                    $method->getName(),
                ));
            }
            /** @var Route $route */
            $route = $routes[0]->newInstance();
            $verb = strtoupper($route->method);
            if (!in_array($verb, self::ALLOWED_VERBS, true)) {
                throw new GeneratorException("Invalid HTTP method {$verb} on {$method->getName()}()");
            }
            $path = $route->path;
            if ($path === '' || !str_starts_with($path, '/')) {
                throw new GeneratorException("Route path must start with / on {$method->getName()}()");
            }
            $fullPath = rtrim($prefix, '/') . $path;
            $routeKey = $verb . ' ' . $fullPath;
            if (isset($seenRoutes[$routeKey])) {
                throw new GeneratorException("Duplicate route {$routeKey} on interface {$interfaceFqcn}");
            }
            $seenRoutes[$routeKey] = true;

            $params = $method->getParameters();
            $reqFqcn = null;
            if (count($params) > 1) {
                throw new GeneratorException("{$method->getName()}(): at most one parameter allowed");
            }
            if (count($params) === 1) {
                $reqType = $params[0]->getType();
                if (!$reqType instanceof \ReflectionNamedType || $reqType->isBuiltin()) {
                    throw new GeneratorException("{$method->getName()}(): parameter must be BaseRequest subclass");
                }
                $reqFqcn = $reqType->getName();
                if (!$this->isContractRequest($reqFqcn)) {
                    throw new GeneratorException("{$method->getName()}(): {$reqFqcn} must extend BaseRequest");
                }
                $uses[$reqFqcn] = $reqFqcn;
            }

            $responseMode = $this->resolveResponseMode($method);
            [$retFqcn, $isVoid, $returnSignature] = $this->validateResponseType($method, $responseMode);
            if ($responseMode === 'json' && !$isVoid && $retFqcn !== null) {
                $uses[$retFqcn] = $retFqcn;
                $needsCovertProperty = true;
            }

            $doc = $this->formatMethodDocblock($method);
            $methodBlocks[] = $doc . $this->emitClientMethod(
                $method->getName(),
                $verb,
                $fullPath,
                $reqFqcn,
                $returnSignature,
                $isVoid,
                $responseMode,
            );
        }

        if ($needsCovertProperty) {
            $uses['InterfaceApi\\Support\\CovertProperty'] = 'InterfaceApi\\Support\\CovertProperty';
        }

        sort($uses);
        $useLines = array_map(static fn (string $fqcn): string => 'use ' . $fqcn . ';', array_values(array_unique($uses)));

        $php = <<<PHP
<?php

declare(strict_types=1);

// @generated

namespace {$clientNs};

{$this->implodeLines($useLines)}

class {$clientShort} extends BaseClientApi
{
    protected string \$serviceName = {$this->exportString($serviceName)};

{$this->implodeLines($methodBlocks, "\n\n")}
}

PHP;

        file_put_contents($clientPath, $php);

        return $clientPath;
    }

    /**
     * Client 落在 Module/{模块}/Client/，命名空间 …\Module\{模块}\Client（不在 Interface 下）。
     *
     * @return array{0: string, 1: string} clientDir, clientNamespace
     */
    private function resolveClientLocation(ReflectionClass $iface): array
    {
        $ifaceDir = dirname($iface->getFileName() ?: '');
        if ($ifaceDir === '' || basename($ifaceDir) !== 'Interface') {
            throw new GeneratorException(
                'Interface must live under Module/{name}/Interface/: ' . $iface->getName(),
            );
        }
        $moduleDir = dirname($ifaceDir);
        $clientDir = $moduleDir . DIRECTORY_SEPARATOR . 'Client';

        $ifaceNs = $iface->getNamespaceName();
        if (!str_ends_with($ifaceNs, '\\Interface')) {
            throw new GeneratorException('Interface namespace must end with \\Interface: ' . $ifaceNs);
        }
        $clientNs = substr($ifaceNs, 0, -strlen('\\Interface')) . '\\Client';

        return [$clientDir, $clientNs];
    }

    /** @return list<ReflectionMethod> */
    private function collectPublicApiMethods(ReflectionClass $iface): array
    {
        $byName = [];
        foreach ($iface->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }
            $byName[$method->getName()] = $method;
        }

        return array_values($byName);
    }

    /** @throws GeneratorException */
    private function validateMethod(ReflectionMethod $method, string $interfaceFqcn): void
    {
        if ($method->getName() === '__construct') {
            throw new GeneratorException('Invalid method __construct on ' . $interfaceFqcn);
        }
    }

    private function formatMethodDocblock(ReflectionMethod $method): string
    {
        $lines = ['    /**'];
        $ops = $method->getAttributes(ApiOperation::class);
        if ($ops !== []) {
            /** @var ApiOperation $op */
            $op = $ops[0]->newInstance();
            $text = $op->getSummary() !== '' ? $op->getSummary() : $op->getDescription();
            if ($text !== '') {
                $lines[] = '     * ' . $text;
            }
        } elseif ($method->getDocComment()) {
            $doc = trim($method->getDocComment());
            foreach (preg_split('/\r\n|\n|\r/', $doc) ?: [] as $raw) {
                $line = trim(preg_replace('/^\/?\*+\/?/', '', $raw) ?? '');
                if ($line === '' || str_starts_with($line, '@')) {
                    continue;
                }
                $lines[] = '     * ' . $line;
                break;
            }
        }
        $lines[] = '     */';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return 'json'|'sse'|'chunked'|'download'
     */
    private function resolveResponseMode(ReflectionMethod $method): string
    {
        $modes = [];
        if ($method->getAttributes(StreamResponse::class) !== []) {
            $modes[] = 'sse';
        }
        if ($method->getAttributes(ChunkedResponse::class) !== []) {
            $modes[] = 'chunked';
        }
        if ($method->getAttributes(DownloadResponse::class) !== []) {
            $modes[] = 'download';
        }
        if (count($modes) > 1) {
            throw new GeneratorException($method->getName() . '(): only one response mode is allowed');
        }

        return $modes[0] ?? 'json';
    }

    /**
     * @return array{0: ?string, 1: bool, 2: string} retFqcn, isVoid, generated return signature
     */
    private function validateResponseType(ReflectionMethod $method, string $responseMode): array
    {
        $retType = $method->getReturnType();
        if ($retType === null) {
            throw new GeneratorException("{$method->getName()}(): return type required");
        }
        if (!$retType instanceof ReflectionNamedType) {
            throw new GeneratorException("{$method->getName()}(): union/intersection return not supported");
        }

        if ($responseMode === 'json') {
            if ($retType->getName() === 'void') {
                return [null, true, 'void'];
            }
            $retFqcn = $retType->getName();
            if ($retType->isBuiltin() || !is_a($retFqcn, BaseResponse::class, true)) {
                throw new GeneratorException("{$method->getName()}(): return must be BaseResponse or void");
            }

            return [$retFqcn, false, $this->shortName($retFqcn)];
        }

        $expected = $responseMode === 'chunked' ? 'string' : 'array';
        if ($retType->getName() === 'void') {
            throw new GeneratorException("{$method->getName()}(): {$responseMode} response cannot return void");
        }
        if (!$retType->isBuiltin() || $retType->getName() !== $expected || $retType->allowsNull()) {
            throw new GeneratorException("{$method->getName()}(): {$responseMode} response must return {$expected}");
        }

        return [null, false, $expected];
    }

    private function emitClientMethod(
        string $name,
        string $verb,
        string $fullPath,
        ?string $reqFqcn,
        string $returnSignature,
        bool $isVoid,
        string $responseMode,
    ): string {
        $reqShort = $reqFqcn !== null ? $this->shortName($reqFqcn) : null;
        $paramList = $reqShort !== null ? "{$reqShort} \$request, array \$options = []" : 'array $options = []';
        $pathLit = var_export($fullPath, true);
        $verbLit = var_export($verb, true);

        $body = ["        \$requestDefaults = [];"];
        if ($reqShort !== null) {
            if (in_array($verb, self::QUERY_VERBS, true)) {
                $body[] = '        $requestDefaults[\'query\'] = $request->toDeepArray();';
            } else {
                $body[] = '        $requestDefaults[\'body\'] = json_encode($request->toDeepArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);';
                if ($responseMode === 'sse' || $responseMode === 'chunked') {
                    $body[] = '        $requestDefaults[\'headers\'][\'Content-Type\'] = \'application/json\';';
                }
            }
        }
        if ($responseMode === 'sse') {
            $body[] = '        $requestDefaults[\'headers\'][\'Accept\'] = \'text/event-stream\';';
        }
        $mergeMethod = $responseMode === 'sse' || $responseMode === 'chunked'
            ? 'mergeStreamClientOptions'
            : 'mergeClientOptions';
        $body[] = "        \$options = \$this->{$mergeMethod}(\$requestDefaults, \$options);";
        $body[] = "        \$response = \$this->requestWithConnectRetry({$verbLit}, \$this->uri({$pathLit}), \$options);";
        if ($responseMode === 'json') {
            if ($isVoid) {
                $body[] = '        $this->parseResponseByHeaders($response);';
                $body[] = '        return;';
            } else {
                $body[] = '        $result = $this->parseResponseByHeaders($response);';
                $body[] = "        return CovertProperty::toCovertDeepProperty(\$result, {$returnSignature}::class);";
            }
        } else {
            $modeLit = var_export($responseMode, true);
            $body[] = "        return \$this->parseResponseByHeaders(\$response, {$modeLit});";
        }

        return '    public function ' . $name . '(' . $paramList . '): ' . $returnSignature . "\n    {\n"
            . implode("\n", $body) . "\n    }";
    }

    private function shortName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function isContractRequest(string $fqcn): bool
    {
        if (!class_exists($fqcn)) {
            return false;
        }

        return is_a($fqcn, BaseRequest::class, true)
            || is_a($fqcn, \Swoolefy\Http\BaseRequest::class, true);
    }

    /** @param list<string> $lines */
    private function implodeLines(array $lines, string $sep = "\n"): string
    {
        return implode($sep, $lines);
    }

    private function exportString(string $value): string
    {
        return var_export($value, true);
    }
}
