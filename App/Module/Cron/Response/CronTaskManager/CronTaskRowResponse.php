<?php

declare(strict_types=1);


namespace App\Module\Cron\Response\CronTaskManager;

use App\Module\Cron\Dto\CronTaskManager\CronTaskRowDto;
use Swoolefy\Http\BaseResponse;

class CronTaskRowResponse extends BaseResponse
{
    protected CronTaskRowDto $row;

    public function __construct(CronTaskRowDto|array $attributes)
    {
        $this->row = $attributes instanceof CronTaskRowDto
            ? $attributes
            : CronTaskRowDto::fromEntityRow($attributes);
    }

    public function getRow(): CronTaskRowDto
    {
        return $this->row;
    }

    public function setRow(CronTaskRowDto $row): static
    {
        $this->row = $row;

        return $this;
    }

    public function getData(): array
    {
        return $this->row->toDeepArray();
    }
}
