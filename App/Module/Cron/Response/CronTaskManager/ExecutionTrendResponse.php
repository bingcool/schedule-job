<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use Swoolefy\Http\BaseResponse;
use App\Module\Cron\Dto\CronTaskManager\ExecutionTrendBucketDto;

class ExecutionTrendResponse extends BaseResponse
{
    /**
     * @var list<ExecutionTrendBucketDto>
     */
    protected array $buckets;

    /**
     * @param list<ExecutionTrendBucketDto> $buckets
     */
    public function __construct(array $buckets)
    {
        $this->buckets = $buckets;
    }

    public function getData(): array
    {
        $list = [];
        foreach ($this->buckets as $bucket) {
            $list[] = $bucket->toDeepArray();
        }

        return $list;
    }
}
