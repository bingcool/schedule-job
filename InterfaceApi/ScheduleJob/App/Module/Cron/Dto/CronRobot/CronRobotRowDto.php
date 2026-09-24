<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\AbstractDto;

class CronRobotRowDto extends AbstractDto
{
    #[ApiProperty(description: '机器人 ID')]
    protected int $id = 0;

    #[ApiProperty(description: '名称')]
    protected string $name = '';

    #[ApiProperty(description: '平台：1-企微 2-钉钉 3-飞书')]
    protected int $platform = 0;

    #[ApiProperty(description: '脱敏后的 Webhook')]
    protected string $webhookUrl = '';

    #[ApiProperty(description: '是否已配置签名密钥')]
    protected bool $secretConfigured = false;

    #[ApiProperty(description: '0-禁用 1-启用')]
    protected int $status = 1;

    #[ApiProperty(description: '最近测试时间')]
    protected string $lastTestAt = '';

    #[ApiProperty(description: '最近测试是否成功')]
    protected int $lastTestOk = 0;

    #[ApiProperty(description: '最近测试失败原因')]
    protected string $lastTestError = '';

    #[ApiProperty(description: '创建时间')]
    protected string $createdAt = '';

    #[ApiProperty(description: '更新时间')]
    protected string $updatedAt = '';

    

    public function getId(): int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getPlatform(): int { return $this->platform; }
    public function setPlatform(int $platform): static { $this->platform = $platform; return $this; }
    public function getWebhookUrl(): string { return $this->webhookUrl; }
    public function setWebhookUrl(string $webhookUrl): static { $this->webhookUrl = $webhookUrl; return $this; }
    public function getSecretConfigured(): bool { return $this->secretConfigured; }
    public function setSecretConfigured(bool $secretConfigured): static { $this->secretConfigured = $secretConfigured; return $this; }
    public function getStatus(): int { return $this->status; }
    public function setStatus(int $status): static { $this->status = $status; return $this; }
    public function getLastTestAt(): string { return $this->lastTestAt; }
    public function setLastTestAt(string $lastTestAt): static { $this->lastTestAt = $lastTestAt; return $this; }
    public function getLastTestOk(): int { return $this->lastTestOk; }
    public function setLastTestOk(int $lastTestOk): static { $this->lastTestOk = $lastTestOk; return $this; }
    public function getLastTestError(): string { return $this->lastTestError; }
    public function setLastTestError(string $lastTestError): static { $this->lastTestError = $lastTestError; return $this; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function setCreatedAt(string $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
    public function setUpdatedAt(string $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
