<?php

namespace App\Router;

use Swoolefy\Http\Middleware\AuthenticateMiddleware;
use Swoolefy\Http\Middleware\CorsMiddleware;
use Swoolefy\Http\Route;
use App\Module\Cron\Controller\CronAdminController;
use App\Module\Cron\Controller\CronRobotController;
use App\Module\Cron\Controller\CronTaskManagerController;
use App\Module\Staff\Middleware\MenuPagePermissionMiddleware;

Route::get('/cron-admin', [
    'dispatch_route' => [CronAdminController::class, 'index'],
]);
foreach (CronAdminController::staticFiles() as $staticFile) {
    Route::get('/cron-admin/' . $staticFile, [
        'dispatch_route' => [CronAdminController::class, 'assets'],
    ]);
}

Route::group([
    'prefix' => 'api/v1',
    'middleware' => [
        CorsMiddleware::class,
        AuthenticateMiddleware::class,
        MenuPagePermissionMiddleware::class,
    ]
], function () {
    // 任务管理
    Route::get('/tasks', [
        'dispatch_route' => [CronTaskManagerController::class, 'listTasks'],
    ]);
    Route::get('/tasks/creators', [
        'dispatch_route' => [CronTaskManagerController::class, 'listTaskCreators'],
    ]);
    Route::post('/tasks', [
        'dispatch_route' => [CronTaskManagerController::class, 'createTask'],
    ]);
    Route::put('/tasks', [
        'dispatch_route' => [CronTaskManagerController::class, 'updateTask'],
    ]);
    Route::delete('/tasks', [
        'dispatch_route' => [CronTaskManagerController::class, 'deleteTask'],
    ]);
    Route::match(['POST', 'PUT'], '/tasks/status', [
        'dispatch_route' => [CronTaskManagerController::class, 'switchTaskStatus'],
    ]);
    Route::put('/tasks/batch-status', [
        'dispatch_route' => [CronTaskManagerController::class, 'batchSwitchStatus'],
    ]);
    Route::get('/tasks/detail', [
        'dispatch_route' => [CronTaskManagerController::class, 'getTask'],
    ]);
    Route::post('/tasks/expression/preview', [
        'dispatch_route' => [CronTaskManagerController::class, 'previewExpression'],
    ]);
    Route::get('/tasks/execution', [
        'dispatch_route' => [CronTaskManagerController::class, 'getExecution'],
    ]);
    Route::post('/executions/cancel', [
        'dispatch_route' => [CronTaskManagerController::class, 'cancelExecution'],
    ]);
    Route::post('/tasks/duplicate', [
        'dispatch_route' => [CronTaskManagerController::class, 'duplicateTask'],
    ]);
    Route::post('/tasks/run', [
        'dispatch_route' => [CronTaskManagerController::class, 'runTaskOnce'],
    ]);
    Route::match(['POST', 'PUT'], '/tasks/owner', [
        'dispatch_route' => [CronTaskManagerController::class, 'transferTaskOwner'],
    ]);

    // 节点管理
    Route::get('/nodes', [
        'dispatch_route' => [CronTaskManagerController::class, 'listNodes'],
    ]);
    Route::post('/nodes', [
        'dispatch_route' => [CronTaskManagerController::class, 'createNode'],
    ]);
    Route::put('/nodes', [
        'dispatch_route' => [CronTaskManagerController::class, 'updateNode'],
    ]);
    Route::get('/nodes/detail', [
        'dispatch_route' => [CronTaskManagerController::class, 'getNode'],
    ]);
    Route::delete('/nodes', [
        'dispatch_route' => [CronTaskManagerController::class, 'deleteNode'],
    ]);

    // 节点分组
    Route::get('/node-groups', [
        'dispatch_route' => [CronTaskManagerController::class, 'listNodeGroups'],
    ]);
    Route::post('/node-groups', [
        'dispatch_route' => [CronTaskManagerController::class, 'createNodeGroup'],
    ]);
    Route::put('/node-groups', [
        'dispatch_route' => [CronTaskManagerController::class, 'updateNodeGroup'],
    ]);
    Route::get('/node-groups/detail', [
        'dispatch_route' => [CronTaskManagerController::class, 'getNodeGroup'],
    ]);
    Route::delete('/node-groups', [
        'dispatch_route' => [CronTaskManagerController::class, 'deleteNodeGroup'],
    ]);

    Route::get('/robots', [
        'dispatch_route' => [CronRobotController::class, 'listRobots'],
    ]);
    Route::get('/robots/detail', [
        'dispatch_route' => [CronRobotController::class, 'getRobot'],
    ]);
    Route::post('/robots', [
        'dispatch_route' => [CronRobotController::class, 'createRobot'],
    ]);
    Route::put('/robots', [
        'dispatch_route' => [CronRobotController::class, 'updateRobot'],
    ]);
    Route::delete('/robots', [
        'dispatch_route' => [CronRobotController::class, 'deleteRobot'],
    ]);
    Route::match(['POST', 'PUT'], '/robots/status', [
        'dispatch_route' => [CronRobotController::class, 'switchStatus'],
    ]);
    Route::post('/robots/test', [
        'dispatch_route' => [CronRobotController::class, 'testRobot'],
    ]);

    // 日志监控
    Route::get('/tasks/logs/trend', [
        'dispatch_route' => [CronTaskManagerController::class, 'taskLogsTrend'],
    ]);
    Route::get('/tasks/logs', [
        'dispatch_route' => [CronTaskManagerController::class, 'taskLogs'],
    ]);
    Route::get('/tasks/operation-logs/operators', [
        'dispatch_route' => [CronTaskManagerController::class, 'listTaskOperationOperators'],
    ]);
    Route::get('/tasks/operation-logs', [
        'dispatch_route' => [CronTaskManagerController::class, 'taskOperationLogs'],
    ]);
    Route::get('/tasks/stats', [
        'dispatch_route' => [CronTaskManagerController::class, 'taskStats'],
    ]);

    // Dashboard / Runtime
    Route::get('/dashboard/overview', [
        'dispatch_route' => [CronTaskManagerController::class, 'dashboardOverview'],
    ]);
    Route::get('/dashboard/execution-trend', [
        'dispatch_route' => [CronTaskManagerController::class, 'executionTrend'],
    ]);
    Route::get('/runtime/overview', [
        'dispatch_route' => [CronTaskManagerController::class, 'runtimeOverview'],
    ]);
});

Route::group([
    'prefix' => 'api/v1',
    'middleware' => [
        CorsMiddleware::class,
    ]
], function () {
    Route::get('/agent/tasks', [
        'dispatch_route' => [CronTaskManagerController::class, 'agentTasks'],
    ]);
    Route::post('/agent/heartbeat', [
        'dispatch_route' => [CronTaskManagerController::class, 'agentHeartbeat'],
    ]);
    Route::post('/agent/report', [
        'dispatch_route' => [CronTaskManagerController::class, 'agentReport'],
    ]);
});
