<?php

declare(strict_types=1);

namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Cron\Dto\CronTaskManager\TaskCreatorOptionsListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class TaskCreatorOptionsResponse extends BaseListResponse
{
    #[ApiProperty(description: '创建人选项 data')]
    protected TaskCreatorOptionsListDataDto $data;

    /**
     * @param TaskCreatorOptionsListDataDto|list<\App\Module\Cron\Dto\CronTaskManager\TaskCreatorOptionDto> $list
     */
    public function __construct(TaskCreatorOptionsListDataDto|array $list)
    {
        $this->data = $list instanceof TaskCreatorOptionsListDataDto
            ? $list
            : TaskCreatorOptionsListDataDto::fromItems($list);
    }

    public function getData(): TaskCreatorOptionsListDataDto
    {
        return $this->data;
    }

    /**
     * @param TaskCreatorOptionsListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof TaskCreatorOptionsListDataDto) {
            throw new InvalidArgumentException('data must be TaskCreatorOptionsListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
