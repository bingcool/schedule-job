<?php

declare(strict_types=1);

namespace InterfaceApi\ScheduleJob\App\Module\Staff\Interface;

use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\ChangePasswordRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\LoginRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Request\StaffManager\UpdateProfileRequest;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\AuthMeResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\ChangePasswordAckResponse;
use InterfaceApi\ScheduleJob\App\Module\Staff\Response\StaffManager\LoginResponse;
use InterfaceApi\Support\Route;
use InterfaceApi\Support\RouteGroup;

#[RouteGroup(prefix: '/api/v1', name: 'staff-auth')]
interface StaffAuthApiInterface
{
    #[Route(method: 'POST', path: '/auth/login')]
    public function login(LoginRequest $request): LoginResponse;

    #[Route(method: 'GET', path: '/auth/me')]
    public function me(): AuthMeResponse;

    #[Route(method: 'POST', path: '/auth/password')]
    public function changePassword(ChangePasswordRequest $request): ChangePasswordAckResponse;

    #[Route(method: 'POST', path: '/auth/profile')]
    public function updateProfile(UpdateProfileRequest $request): AuthMeResponse;
}
