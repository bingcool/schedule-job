<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use Swoolefy\Http\BaseResponse;

class AuthMeResponse extends BaseResponse
{
    /**
     * @var array<string, mixed>
     */
    protected array $user;

    /**
     * @param array<string, mixed> $user
     */
    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function getData(): array
    {
        return $this->user;
    }
}
