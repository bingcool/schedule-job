<?php

namespace App\Router;

use App\Module\Staff\Controller\StaffAuthController;
use App\Module\Staff\Controller\StaffRoleController;
use App\Module\Staff\Controller\StaffUserController;
use Swoolefy\Http\Middleware\AuthenticateMiddleware;
use Swoolefy\Http\Middleware\CorsMiddleware;
use Swoolefy\Http\Route;

Route::group([
    'prefix' => 'api/v1',
    'middleware' => [
        CorsMiddleware::class,
    ],
], function () {
    Route::post('/auth/register', [
        'dispatch_route' => [StaffAuthController::class, 'register'],
    ]);
    Route::post('/auth/login', [
        'dispatch_route' => [StaffAuthController::class, 'login'],
    ]);
});

Route::group([
    'prefix' => 'api/v1',
    'middleware' => [
        CorsMiddleware::class,
        AuthenticateMiddleware::class,
    ],
], function () {
    Route::get('/auth/me', [
        'dispatch_route' => [StaffAuthController::class, 'me'],
    ]);

    Route::get('/users', [
        'dispatch_route' => [StaffUserController::class, 'listUsers'],
    ]);
    Route::post('/users', [
        'dispatch_route' => [StaffUserController::class, 'createUser'],
    ]);
    Route::put('/users', [
        'dispatch_route' => [StaffUserController::class, 'updateUser'],
    ]);
    Route::get('/users/detail', [
        'dispatch_route' => [StaffUserController::class, 'getUser'],
    ]);
    Route::delete('/users', [
        'dispatch_route' => [StaffUserController::class, 'deleteUser'],
    ]);
    Route::match(['POST', 'PUT'], '/users/status', [
        'dispatch_route' => [StaffUserController::class, 'switchStatus'],
    ]);

    Route::get('/roles', [
        'dispatch_route' => [StaffRoleController::class, 'listRoles'],
    ]);
    Route::get('/roles/stats', [
        'dispatch_route' => [StaffRoleController::class, 'roleStats'],
    ]);
    Route::get('/roles/options', [
        'dispatch_route' => [StaffRoleController::class, 'listRoleOptions'],
    ]);
    Route::post('/roles', [
        'dispatch_route' => [StaffRoleController::class, 'createRole'],
    ]);
    Route::put('/roles', [
        'dispatch_route' => [StaffRoleController::class, 'updateRole'],
    ]);
    Route::get('/roles/detail', [
        'dispatch_route' => [StaffRoleController::class, 'getRole'],
    ]);
    Route::delete('/roles', [
        'dispatch_route' => [StaffRoleController::class, 'deleteRole'],
    ]);
    Route::match(['POST', 'PUT'], '/roles/status', [
        'dispatch_route' => [StaffRoleController::class, 'switchStatus'],
    ]);

    Route::get('/menus', [
        'dispatch_route' => [StaffRoleController::class, 'listMenus'],
    ]);
    Route::post('/menus', [
        'dispatch_route' => [StaffRoleController::class, 'createMenu'],
    ]);
    Route::put('/menus', [
        'dispatch_route' => [StaffRoleController::class, 'updateMenu'],
    ]);
    Route::get('/menus/detail', [
        'dispatch_route' => [StaffRoleController::class, 'getMenu'],
    ]);
    Route::delete('/menus', [
        'dispatch_route' => [StaffRoleController::class, 'deleteMenu'],
    ]);
});
