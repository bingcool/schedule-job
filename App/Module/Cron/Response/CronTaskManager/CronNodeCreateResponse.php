<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

/**
 * 创建节点响应：data 中含 apiKey（仅创建时返回，列表/详情不返回）。
 */
class CronNodeCreateResponse extends CronNodeRowResponse
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
        $apiKey = (string) ($attributes['api_key'] ?? '');
        if ($apiKey !== '') {
            $this->data->setApiKey($apiKey);
        }
    }
}
