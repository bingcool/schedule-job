<?php

declare(strict_types=1);

namespace App\Module\Cron\Dto\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Core\Dto\AbstractDto;

class TaskOperationOperatorOptionDto extends AbstractDto
{
    #[ApiProperty(description: '操作人 staff_user.id')]
    protected int $operatorId = 0;

    #[ApiProperty(description: '操作人展示名：user_name(account)')]
    protected string $operatorName = '';

    public static function fromRow(int $operatorId, string $userName, string $account): self
    {
        $dto = new self();
        $dto->setOperatorId($operatorId);
        $dto->setOperatorName(TaskCreatorOptionDto::formatStaffUserName($userName, $account));

        return $dto;
    }

    public function getOperatorId(): int
    {
        return $this->operatorId;
    }

    public function setOperatorId(int $operatorId): static
    {
        $this->operatorId = $operatorId;

        return $this;
    }

    public function getOperatorName(): string
    {
        return $this->operatorName;
    }

    public function setOperatorName(string $operatorName): static
    {
        $this->operatorName = $operatorName;

        return $this;
    }
}
