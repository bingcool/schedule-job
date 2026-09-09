<?php

declare(strict_types=1);

namespace App\Module\Cron\Request\CronRobot;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class CronRobotUpdateRequest extends BaseRequest
{
    #[ApiProperty(description: '机器人 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    #[ApiProperty(description: '名称')]
    #[ValidationRule(rule: 'required|string', message: 'name 不能为空')]
    protected string $name = '';

    #[ApiProperty(description: '平台：1-企微 2-钉钉 3-飞书')]
    #[ValidationRule(rule: 'required|int', message: 'platform 不能为空')]
    #[StringToInt]
    protected int $platform = 0;

    #[ApiProperty(description: 'Webhook；空表示不改')]
    protected ?string $webhookUrl = null;

    #[ApiProperty(description: '签名密钥；空表示不改')]
    protected ?string $secret = null;

    public function getId(): int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getName(): string { return trim($this->name); }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getPlatform(): int { return $this->platform; }
    public function setPlatform(int $platform): static { $this->platform = $platform; return $this; }
    public function getWebhookUrl(): string { return trim((string) ($this->webhookUrl ?? '')); }
    public function setWebhookUrl(?string $webhookUrl): static { $this->webhookUrl = $webhookUrl; return $this; }
    public function getSecret(): string { return trim((string) ($this->secret ?? '')); }
    public function setSecret(?string $secret): static { $this->secret = $secret; return $this; }
}
