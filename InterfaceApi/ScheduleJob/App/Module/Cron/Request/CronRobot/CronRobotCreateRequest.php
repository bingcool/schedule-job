<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\StringToInt;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class CronRobotCreateRequest extends BaseRequest
{
    #[ApiProperty(description: '名称')]
    #[ValidationRule(rule: 'required|string', message: 'name 不能为空')]
    protected string $name = '';

    #[ApiProperty(description: '平台：1-企微 2-钉钉 3-飞书')]
    #[ValidationRule(rule: 'required|int', message: 'platform 不能为空')]
    #[StringToInt]
    protected int $platform = 0;

    #[ApiProperty(description: 'Webhook 完整地址')]
    #[ValidationRule(rule: 'required|string', message: 'webhookUrl 不能为空')]
    protected string $webhookUrl = '';

    #[ApiProperty(description: '签名密钥，可空')]
    protected ?string $secret = null;

    public function getName(): string { return trim($this->name); }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getPlatform(): int { return $this->platform; }
    public function setPlatform(int $platform): static { $this->platform = $platform; return $this; }
    public function getWebhookUrl(): string { return trim($this->webhookUrl); }
    public function setWebhookUrl(string $webhookUrl): static { $this->webhookUrl = $webhookUrl; return $this; }
    public function getSecret(): string { return trim((string) ($this->secret ?? '')); }
    public function setSecret(?string $secret): static { $this->secret = $secret; return $this; }
}
