<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\AgentTasksResultDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Http\BaseResponse;

class CronAgentTasksResponse extends BaseResponse
{
    #[ApiProperty(description: 'Agent 待执行任务 data')]
    protected AgentTasksResultDto $data;

    public function __construct(AgentTasksResultDto $data)
    {
        $this->data = $data;
    }

    public function getData(): AgentTasksResultDto
    {
        return $this->data;
    }

    /**
     * @param AgentTasksResultDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof AgentTasksResultDto) {
            throw new InvalidArgumentException('data must be AgentTasksResultDto');
        }
        $this->data = $data;

        return $this;
    }
}
