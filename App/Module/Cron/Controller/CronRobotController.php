<?php

declare(strict_types=1);

namespace App\Module\Cron\Controller;

use App\Module\Cron\Dto\CronRobot\CreateRobotDto;
use App\Module\Cron\Dto\CronRobot\RobotIdDto;
use App\Module\Cron\Dto\CronRobot\SwitchRobotStatusDto;
use App\Module\Cron\Dto\CronRobot\UpdateRobotDto;
use App\Module\Cron\Request\CronRobot\CronRobotCreateRequest;
use App\Module\Cron\Request\CronRobot\CronRobotIdRequest;
use App\Module\Cron\Request\CronRobot\CronRobotStatusRequest;
use App\Module\Cron\Request\CronRobot\CronRobotUpdateRequest;
use App\Module\Cron\Response\CronRobot\CronRobotListResponse;
use App\Module\Cron\Response\CronRobot\CronRobotRowResponse;
use App\Module\Cron\Response\CronRobot\CronRobotTestResponse;
use App\Module\Cron\Response\CronTaskManager\CronDeleteAckResponse;
use App\Module\Cron\Service\CronRobotService;
use Swoolefy\Annotation\ApiOperation;
use Swoolefy\Core\Controller\BController;

class CronRobotController extends BController
{
    private CronRobotService $cronRobotService {
        get => $this->cronRobotService ??= new CronRobotService();
    }

    /**
     * Route: GET /api/v1/robots
     */
    #[ApiOperation('机器人列表')]
    public function listRobots(): CronRobotListResponse
    {
        return new CronRobotListResponse($this->cronRobotService->listRobots());
    }

    /**
     * Route: GET /api/v1/robots/detail?id=
     */
    #[ApiOperation('机器人详情（脱敏）')]
    public function getRobot(CronRobotIdRequest $request): CronRobotRowResponse
    {
        return new CronRobotRowResponse(
            $this->cronRobotService->getRobot(RobotIdDto::of($request->getId()))
        );
    }

    /**
     * Route: POST /api/v1/robots
     */
    #[ApiOperation('创建机器人')]
    public function createRobot(CronRobotCreateRequest $request): CronRobotRowResponse
    {
        $dto = (new CreateRobotDto())
            ->setName($request->getName())
            ->setPlatform($request->getPlatform())
            ->setWebhookUrl($request->getWebhookUrl())
            ->setSecret($request->getSecret());

        return new CronRobotRowResponse($this->cronRobotService->createRobot($dto));
    }

    /**
     * Route: PUT /api/v1/robots
     */
    #[ApiOperation('更新机器人')]
    public function updateRobot(CronRobotUpdateRequest $request): CronRobotRowResponse
    {
        $dto = (new UpdateRobotDto())
            ->setId($request->getId())
            ->setName($request->getName())
            ->setPlatform($request->getPlatform())
            ->setWebhookUrl($request->getWebhookUrl())
            ->setSecret($request->getSecret());

        return new CronRobotRowResponse($this->cronRobotService->updateRobot($dto));
    }

    /**
     * Route: DELETE /api/v1/robots
     */
    #[ApiOperation('删除机器人')]
    public function deleteRobot(CronRobotIdRequest $request): CronDeleteAckResponse
    {
        $id = $this->cronRobotService->deleteRobot(RobotIdDto::of($request->getId()));

        return new CronDeleteAckResponse($id);
    }

    /**
     * Route: PUT /api/v1/robots/status
     */
    #[ApiOperation('启用或禁用机器人')]
    public function switchStatus(CronRobotStatusRequest $request): CronRobotRowResponse
    {
        $dto = (new SwitchRobotStatusDto())
            ->setId($request->getId())
            ->setStatus($request->getStatus());

        return new CronRobotRowResponse($this->cronRobotService->switchStatus($dto));
    }

    /**
     * Route: POST /api/v1/robots/test
     */
    #[ApiOperation('发送机器人连通测试')]
    public function testRobot(CronRobotIdRequest $request): CronRobotTestResponse
    {
        $result = $this->cronRobotService->testRobot(RobotIdDto::of($request->getId()));

        return new CronRobotTestResponse($result->isOk(), $result->getError());
    }
}
