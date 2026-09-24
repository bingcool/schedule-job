<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Request\CronTaskManager;

use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\ValidationRule;
use InterfaceApi\Support\BaseRequest;

class CronNodeGroupCreateRequest extends BaseRequest
{
    #[ApiProperty(description: '分组名称')]
    #[ValidationRule(rule: 'required|string', message: 'groupName 不能为空')]
    protected string $groupName = '';

    #[ApiProperty(description: '备注')]
    protected ?string $remark = null;

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
}
