<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Cron\Response\CronTaskManager;

use InterfaceApi\ScheduleJob\App\Module\Common\Dto\DeleteAckDto;
use InvalidArgumentException;
use InterfaceApi\Support\ApiProperty;
use InterfaceApi\Support\BaseResponse;

class CronDeleteAckResponse extends BaseResponse
{
    #[ApiProperty(description: '删除确认 data')]
    protected DeleteAckDto $data;

    public function __construct(int $id, bool $deleted = true)
    {
        $this->data = DeleteAckDto::of($id, $deleted);
    }

    public function getData(): DeleteAckDto
    {
        return $this->data;
    }

    /**
     * @param DeleteAckDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof DeleteAckDto) {
            throw new InvalidArgumentException('data must be DeleteAckDto');
        }
        $this->data = $data;

        return $this;
    }
}
