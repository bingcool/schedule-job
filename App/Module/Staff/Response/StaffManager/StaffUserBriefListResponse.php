<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Common\Http\BaseListResponse;
use App\Module\Staff\Dto\StaffUser\StaffUserBriefListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;

class StaffUserBriefListResponse extends BaseListResponse
{
    #[ApiProperty(description: '用户简要列表 data')]
    protected StaffUserBriefListDataDto $data;

    /**
     * @param StaffUserBriefListDataDto|list<\App\Module\Staff\Dto\StaffUser\StaffUserBriefDto> $list
     */
    public function __construct(StaffUserBriefListDataDto|array $list)
    {
        $this->data = $list instanceof StaffUserBriefListDataDto
            ? $list
            : StaffUserBriefListDataDto::fromItems($list);
    }

    public function getData(): StaffUserBriefListDataDto
    {
        return $this->data;
    }

    /**
     * @param StaffUserBriefListDataDto $data
     * @return $this
     */
    public function setData($data): static
    {
        if (!$data instanceof StaffUserBriefListDataDto) {
            throw new InvalidArgumentException('data must be StaffUserBriefListDataDto');
        }
        $this->data = $data;

        return $this;
    }
}
