<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\CronAgentNodeRowDto;

/**
 * 创建节点响应：data 中含 apiKey（仅创建时返回，列表/详情不返回）。
 */
class CronNodeCreateResponse extends CronNodeRowResponse
{
    public function __construct(CronAgentNodeRowDto|array $attributes)
    {
        parent::__construct($attributes);
        if ($attributes instanceof CronAgentNodeRowDto) {
            return;
        }
        $apiKey = (string) ($attributes['api_key'] ?? '');
        if ($apiKey !== '') {
            $this->data->setApiKey($apiKey);
        }
    }
}
