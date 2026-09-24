<?php

declare(strict_types=1);

namespace InterfaceApi\Support\Generator;

/**
 * 各模块 Client/ 目录说明（随 generate-client 同步）。
 */
final class ClientDirectoryReadme
{
    public static function sync(string $clientDir): void
    {
        if (!is_dir($clientDir)) {
            return;
        }

        $path = $clientDir . DIRECTORY_SEPARATOR . 'README.md';
        $content = <<<'MD'
# HTTP Client（自动生成）

本目录下的 `*Api.php` 由 **InterfaceApi 契约接口**（同级模块 `Interface/` 中的 `*ApiInterface`）经脚本生成，文件头含 `// @generated` 标记。

## 如何更新

在 schedule-job 仓库根目录执行：

```bash
php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App
```

修改路由或方法签名时，请先改 `Interface/` 下的接口与 Request/Response，再运行上述命令重新生成 Client。

## 禁止直接编辑

- **请勿**手工修改本目录中的 `*.php` Client 实现。
- **禁止** AI、Agent、Copilot 等工具直接编辑或「优化」这些文件；变更应通过契约接口与生成脚本完成。

MD;

        file_put_contents($path, $content);
    }

    /**
     * @param list<string> $writtenClientPhpPaths
     */
    public static function syncForWrittenClients(array $writtenClientPhpPaths): void
    {
        $dirs = [];
        foreach ($writtenClientPhpPaths as $file) {
            $dir = dirname($file);
            $dirs[$dir] = true;
        }
        foreach (array_keys($dirs) as $clientDir) {
            self::sync($clientDir);
        }
    }
}
