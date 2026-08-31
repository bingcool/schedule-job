<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffManager\StaffUserRowDto;
use InvalidArgumentException;
use Swoolefy\Annotation\ArrayList;
use Swoolefy\Core\Dto\ArrayDto;

class ListUsersPageResult extends ArrayDto
{
    protected int $total = 0;

    protected int $page = 1;

    protected int $pageSize = 20;

    /**
     * @var array<int, StaffUserRowDto>
     */
    #[ArrayList(itemClass: StaffUserRowDto::class)]
    protected array $list = [];

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setPage(int $page): static
    {
        $this->page = max(1, $page);

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPageSize(int $pageSize): static
    {
        $this->pageSize = max(1, $pageSize);

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return array<int, StaffUserRowDto>
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @param array<int, StaffUserRowDto> $list
     */
    public function setList(array $list): static
    {
        if ($list !== [] && !($list[0] instanceof StaffUserRowDto)) {
            throw new InvalidArgumentException('list items must be instances of StaffUserRowDto');
        }
        $this->list = $list;

        return $this;
    }

    public function addListItem(StaffUserRowDto $item): static
    {
        $this->list[] = $item;

        return $this;
    }
}
