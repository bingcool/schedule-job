<?php

declare(strict_types=1);

namespace App\Module\Cron;

/**
 * cron_agent_node.liveness_status：已告警/已知的存活状态，用于上线/失联边沿去重。
 */
final class NodeLivenessStatus
{
    /** 从未标记边沿（新建或升级后尚未同步）。曾有心跳且已过期时仍发失联。 */
    public const UNKNOWN = 0;

    /** 最近一次边沿为在线。 */
    public const ONLINE = 1;

    /** 最近一次边沿为失联。 */
    public const OFFLINE = 2;
}
