<?php

/**
 * Auth / JWT 配置模版。
 *
 * 使用：create 应用时复制到 `Config/auth.php`，并注册 component：
 * ```php
 * // Config/component/auth.php
 * 'auth.guard' => fn () => new \Swoolefy\Support\Auth\JwtAuthGuard(
 *     (include APP_PATH . '/Config/auth.php')['jwt'] ?? []
 * ),
 * ```
 *
 * | 键 | 说明 |
 * |----|------|
 * | jwt.secret | 生产必填 AUTH_JWT_SECRET |
 * | jwt.algo | HS256（默认）/ HS384 / HS512 |
 * | jwt.ttl_seconds | 签发建议 TTL；校验看 token exp |
 * | jwt.issuer / audience | 空串 = 不校验 |
 * | jwt.id_claim | 默认 uid（其次标准 sub） |
 * | jwt.roles_claim | 保留键名。签发与验票都不读取；角色由 auth.role_resolver 提供 |
 * | jwt.tenant_claim | 租户 claim 名 |
 *
 * @see docs/Auth.md
 */

return [
    'jwt' => [
        'secret' => env('AUTH_JWT_SECRET', 'schedule-job-dev-jwt-secret-change-me'),
        'algo' => env('AUTH_JWT_ALGO', 'HS256'),
        'ttl_seconds' => (int) env('AUTH_JWT_TTL', 3600),
        'issuer' => env('AUTH_JWT_ISSUER', ''),
        'audience' => env('AUTH_JWT_AUDIENCE', ''),
        'id_claim' => 'uid',
        'roles_claim' => 'roles',
        'tenant_claim' => 'tenant_id',
    ],
    'http' => [
        'bearer_header' => 'authorization',
    ],
];
