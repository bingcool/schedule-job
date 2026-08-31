<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Http\BaseResponse;

class RoleStatsResponse extends BaseResponse
{
    /**
     * @var array<string, mixed>
     */
    protected array $stats;

    /**
     * @param array<string, mixed> $stats
     */
    public function __construct(array $stats)
    {
        $this->stats = $stats;
    }

    public function getData(): array
    {
        return $this->stats;
    }
}
