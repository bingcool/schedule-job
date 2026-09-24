<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto;

/**
 * 创建节点响应：data 中含 apiKey（仅创建时返回，列表/详情不返回）。
 */
class CronNodeCreateResponse extends CronNodeRowResponse
{
    public function __construct(CronAgentNodeRowDto|array $attributes)
    {
        if ($attributes instanceof CronAgentNodeRowDto) {
            parent::__construct($attributes);

            return;
        }
        parent::__construct($attributes);
        $apiKey = (string) ($attributes['api_key'] ?? '');
        if ($apiKey !== '') {
            $this->getData()->setApiKey($apiKey);
        }
    }
}
