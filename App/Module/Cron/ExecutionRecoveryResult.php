<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * Lease Recovery CAS 结果。候选 SELECT 不是恢复权，本对象才是。
 */
final class ExecutionRecoveryResult
{
    public const RECOVERED = 'recovered';

    public const CAS_CONFLICT = 'cas_conflict';

    public const NOT_EXPIRED = 'not_expired';

    public const DEFERRED = 'deferred';

    public const NOT_RECOVERABLE = 'not_recoverable';

    public function __construct(
        public readonly string $code,
        public readonly bool $recovered = false,
        public readonly ?string $deleteNamespace = null,
        public readonly ?string $deleteJobName = null,
    ) {
    }

    public static function recovered(?string $deleteNamespace = null, ?string $deleteJobName = null): self
    {
        return new self(self::RECOVERED, true, $deleteNamespace, $deleteJobName);
    }

    public static function casConflict(): self
    {
        return new self(self::CAS_CONFLICT);
    }

    public static function notExpired(): self
    {
        return new self(self::NOT_EXPIRED);
    }

    public static function deferred(): self
    {
        return new self(self::DEFERRED);
    }

    public static function notRecoverable(): self
    {
        return new self(self::NOT_RECOVERABLE);
    }

    public function shouldDeleteJob(): bool
    {
        return $this->recovered
            && $this->deleteNamespace !== null
            && $this->deleteNamespace !== ''
            && $this->deleteJobName !== null
            && $this->deleteJobName !== '';
    }
}
