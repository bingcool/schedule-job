<?php

declare(strict_types=1);

namespace App\Module\Staff\Response\StaffManager;

use App\Module\Staff\Dto\StaffAuth\AuthMeProfileDto;
use Swoolefy\Http\BaseResponse;

class AuthMeResponse extends BaseResponse
{
    protected AuthMeProfileDto $user;

    public function __construct(AuthMeProfileDto $user)
    {
        $this->user = $user;
    }

    public function getData(): array
    {
        return $this->user->toDeepArray();
    }
}
