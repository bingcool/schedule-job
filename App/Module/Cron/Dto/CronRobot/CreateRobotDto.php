<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronRobot;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class CreateRobotDto extends AbstractDto
{
    #[ApiProperty(description: '名称')]
    protected string $name = '';

    #[ApiProperty(description: '平台：1-企微 2-钉钉 3-飞书')]
    protected int $platform = 0;

    #[ApiProperty(description: 'Webhook 完整地址')]
    protected string $webhookUrl = '';

    #[ApiProperty(description: '签名密钥，可空')]
    protected string $secret = '';

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getPlatform(): int { return $this->platform; }
    public function setPlatform(int $platform): static { $this->platform = $platform; return $this; }
    public function getWebhookUrl(): string { return $this->webhookUrl; }
    public function setWebhookUrl(string $webhookUrl): static { $this->webhookUrl = $webhookUrl; return $this; }
    public function getSecret(): string { return $this->secret; }
    public function setSecret(string $secret): static { $this->secret = $secret; return $this; }
}
