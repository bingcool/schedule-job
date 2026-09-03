<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

/**
 * 计划任务创建人下拉选项。
 */
class TaskCreatorOptionDto extends AbstractDto
{
    #[ApiProperty(description: '创建人 staff_user.id')]
    protected int $staffUserId = 0;

    #[ApiProperty(description: '创建人展示名：user_name(account)')]
    protected string $staffUserName = '';

    public static function fromRow(int $staffUserId, string $userName, string $account): self
    {
        $dto = new self();
        $dto->setStaffUserId($staffUserId);
        $dto->setStaffUserName(self::formatStaffUserName($userName, $account));

        return $dto;
    }

    public static function formatStaffUserName(string $userName, string $account): string
    {
        $userName = trim($userName);
        $account = trim($account);
        if ($userName !== '' && $account !== '') {
            return $userName . '(' . $account . ')';
        }
        if ($userName !== '') {
            return $userName;
        }

        return $account;
    }

    public function getStaffUserId(): int
    {
        return $this->staffUserId;
    }

    public function setStaffUserId(int $staffUserId): static
    {
        $this->staffUserId = $staffUserId;

        return $this;
    }

    public function getStaffUserName(): string
    {
        return $this->staffUserName;
    }

    public function setStaffUserName(string $staffUserName): static
    {
        $this->staffUserName = $staffUserName;

        return $this;
    }
}
