<?php

declare(strict_types=1);

/**
 * ApiDoc 可选配置：swoolefy `gen:apidoc`（扫 Router）或 InterfaceApi `generate-openapi.php`（扫契约接口）均会读取本文件中的 version 等。
 *
 * 模块级 title/description 优先读 Router/api_router_module.json；
 * 本文件提供全局兜底与版本号。未配置时 title 默认为「{App} · {Module}」。
 *
 * @see \Swoolefy\Script\ApiDoc\ApiDocGenerator
 * @see src/Script/ApiDoc/README.md
 */

return [
    'apidoc' => [
        // 非空时覆盖所有模块的 info.title（一般留空，用模块 JSON 或默认模板）
        'title' => env('APIDOC_TITLE', ''),
        // 非空时覆盖所有模块的 info.description
        'description' => env('APIDOC_DESCRIPTION', ''),
        'version' => env('APIDOC_VERSION', '1.0.0'),
        // 无模块 JSON title 且未设全局 title 时使用；占位符 {app} {module}
        'default_title_template' => env('APIDOC_TITLE_TEMPLATE', '{app} · {module}'),
    ],
];
