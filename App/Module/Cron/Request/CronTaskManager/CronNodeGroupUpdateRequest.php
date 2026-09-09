<?php

declare(strict_types=1);

namespace App\Module\Cron\Request\CronTaskManager;

use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\StringToInt;
use Swoolefy\Annotation\Validation\ValidationRule;
use Swoolefy\Http\BaseRequest;

class CronNodeGroupUpdateRequest extends BaseRequest
{
    #[ApiProperty(description: '分组 ID')]
    #[ValidationRule(rule: 'required|int', message: 'id 不能为空')]
    #[StringToInt]
    protected int $id = 0;

    #[ApiProperty(description: '分组名称')]
    #[ValidationRule(rule: 'required|string', message: 'groupName 不能为空')]
    protected string $groupName = '';

    #[ApiProperty(description: '备注')]
    protected ?string $remark = null;

    #[ApiProperty(description: '绑定的机器人 ID；0=不告警；省略则不改')]
    #[StringToInt]
    protected ?int $robotId = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getGroupName(): string
    {
        return trim($this->groupName);
    }

    public function setGroupName(string $groupName): static
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getRemark(): string
    {
        return trim((string)($this->remark ?? ''));
    }

    public function setRemark(?string $remark): static
    {
        $this->remark = $remark;

        return $this;
    }

    public function getRobotId(): ?int
    {
        return $this->robotId;
    }

    public function setRobotId(?int $robotId): static
    {
        $this->robotId = $robotId;

        return $this;
    }
}
