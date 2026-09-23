<?php

declare(strict_types=1);

namespace App\Module\Staff\Dto\StaffRole;

use App\Module\Common\Dto\AbstractListDataDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ApiProperty;
use Swoolefy\Annotation\ArrayList;

class RoleOptionsListDataDto extends AbstractListDataDto
{
    /**
     * @var array<int, StaffRoleOptionDto>
     */
    #[ApiProperty(description: '角色下拉选项')]
    #[ArrayList(itemClass: StaffRoleOptionDto::class)]
    protected array $list = [];

    /**
     * @return array<int, StaffRoleOptionDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, StaffRoleOptionDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof StaffRoleOptionDto)) {
            throw new InvalidArgumentException('list items must be instances of StaffRoleOptionDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(StaffRoleOptionDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }

    /**
     * @param list<StaffRoleOptionDto> $items
     */
    public static function fromItems(array $items): self
    {
        $dto = new self();
        foreach ($items as $item) {
            $dto->addListItem($item);
        }
        $dto->setTotal(count($dto->getList()));

        return $dto;
    }
}
