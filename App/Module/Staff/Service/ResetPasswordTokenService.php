<?php

declare(strict_types=1);

namespace App\Module\Staff\Service;

use DateTimeImmutable;
use DateTimeZone;
use Swoolefy\Library\Jwt\Configuration;
use Swoolefy\Library\Jwt\Signer\Hmac\Sha256;
use Swoolefy\Library\Jwt\Signer\Key\InMemory;

/**
 * 临时重置密码的签发与解析。
 *
 * 标准 JWT 字符串过长，不适合当登录密码。这里复用 {@see \Swoolefy\Library\Jwt}
 * 的 HS256 对称签名，压成固定 32 位：
 * - 8 字节 payload：uid(4) + 过期 unix 时间(4)
 * - 16 字节 MAC：Jwt HS256 签名的前 16 字节
 * - Base64URL(24 字节) = 正好 32 个字符
 *
 * 密钥与登录 session JWT 隔离，避免重置密码被当成 Bearer token。
 * 登录时能验签即视为临时密码；验签失败则走普通密码。
 */
class ResetPasswordTokenService
{
    /** 临时密码默认有效天数。 */
    public const TTL_DAYS = 3;

    /** 前端展示 / 用户登录使用的密码长度。 */
    public const PASSWORD_LENGTH = 32;

    /**
     * 为指定用户签发 32 位临时重置密码，默认 3 天后过期。
     *
     * @return array{password: string, expiresAt: string, expiresAtTs: int}
     */
    public function issue(int $userId): array
    {
        $now = $this->now();
        $expiresAt = $now->modify('+' . $this->ttlDays() . ' day');
        // 大端 uint32：用户 ID + 过期时间，便于解析且长度固定
        $payload = pack('NN', $userId, $expiresAt->getTimestamp());
        $password = $this->encode($payload . $this->mac($payload));

        return [
            'password' => $password,
            'expiresAt' => $expiresAt->format('Y-m-d H:i:s'),
            'expiresAtTs' => $expiresAt->getTimestamp(),
        ];
    }

    /**
     * 解析用户输入的密码。
     * 长度不对、解码失败或 MAC 不对时返回 null（按普通密码处理）。
     * 验签成功即使已过期也返回 payload，由调用方提示「临时重置密码已过期」。
     *
     * @return array{userId: int, expiresAt: string, expiresAtTs: int, expired: bool}|null
     */
    public function parse(string $password): ?array
    {
        $password = trim($password);
        if (strlen($password) !== self::PASSWORD_LENGTH) {
            return null;
        }

        $raw = $this->decode($password);
        if ($raw === null || strlen($raw) !== 24) {
            return null;
        }

        $payload = substr($raw, 0, 8);
        $mac = substr($raw, 8, 16);
        if (!hash_equals($this->mac($payload), $mac)) {
            return null;
        }

        $parts = unpack('NuserId/Nexp', $payload);
        $userId = (int) ($parts['userId'] ?? 0);
        $expTs = (int) ($parts['exp'] ?? 0);
        if ($userId <= 0 || $expTs <= 0) {
            return null;
        }

        $expiresAt = (new DateTimeImmutable('@' . $expTs))->setTimezone($this->timezone());

        return [
            'userId' => $userId,
            'expiresAt' => $expiresAt->format('Y-m-d H:i:s'),
            'expiresAtTs' => $expTs,
            'expired' => $expTs <= $this->now()->getTimestamp(),
        ];
    }

    /** 用 Jwt HS256 对 payload 签名，截取 16 字节以凑齐 32 位密码。 */
    private function mac(string $payload): string
    {
        $configuration = $this->configuration();

        return substr($configuration->signer()->sign($payload, $configuration->signingKey()), 0, 16);
    }

    /** 24 字节原始数据 → 32 位 URL 安全 Base64（无 padding）。 */
    private function encode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /** 32 位密码还原为原始字节；非法 Base64 返回 null。 */
    private function decode(string $value): ?string
    {
        $pad = strlen($value) % 4;
        if ($pad > 0) {
            $value .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode(strtr($value, '-_', '+/'), true);

        return $raw === false ? null : $raw;
    }

    /** 与登录 JWT 相同的对称算法，但使用独立密钥。 */
    private function configuration(): Configuration
    {
        return Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->secret()),
        );
    }

    /**
     * 优先读 RESET_PASSWORD_JWT_SECRET（至少 16 位）。
     * 未配置时从 AUTH_JWT_SECRET 派生，保证与 session token 密钥不同。
     */
    private function secret(): string
    {
        $secret = trim((string) env('RESET_PASSWORD_JWT_SECRET', ''));
        if ($secret === '') {
            $base = (string) env('AUTH_JWT_SECRET', 'schedule-job-dev-jwt-secret-change-me');
            $secret = hash('sha256', $base . ':reset-password');
        }
        if (strlen($secret) < 16) {
            $secret = str_pad($secret, 16, '0');
        }

        return $secret;
    }

    /** 有效天数，可用 RESET_PASSWORD_TTL_DAYS 覆盖，非法值回落 3 天。 */
    private function ttlDays(): int
    {
        $days = (int) env('RESET_PASSWORD_TTL_DAYS', self::TTL_DAYS);

        return $days > 0 ? $days : self::TTL_DAYS;
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone());
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone(date_default_timezone_get());
    }
}
