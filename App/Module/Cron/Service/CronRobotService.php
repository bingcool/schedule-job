<?php

declare(strict_types=1);

namespace App\Module\Cron\Service;

use App\Module\Cron\Dto\CronRobot\CreateRobotDto;
use App\Module\Cron\Dto\CronRobot\RobotIdDto;
use App\Module\Cron\Dto\CronRobot\SwitchRobotStatusDto;
use App\Module\Cron\Dto\CronRobot\UpdateRobotDto;
use App\Module\Cron\Entity\CronAgentNodeGroupEntity;
use App\Module\Cron\Entity\CronRobotEntity;
use App\Module\Cron\Exception\CronTaskException;
use App\Module\Cron\Robot\RobotAlertMessage;
use App\Module\Cron\Robot\RobotConfig;
use App\Module\Cron\Robot\RobotStrategyFactory;
use App\Module\Cron\Robot\RobotWebhookMask;
use App\Module\Cron\RobotPlatform;
use App\Module\Staff\Service\StaffUserService;
use Swoolefy\Support\Auth\AuthException;
use Swoolefy\Support\FrameworkContext;

class CronRobotService
{
    private StaffUserService $staffUserService {
        get => $this->staffUserService ??= new StaffUserService();
    }

    private RobotStrategyFactory $strategyFactory {
        get => $this->strategyFactory ??= new RobotStrategyFactory();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRobots(): array
    {
        $list = CronRobotEntity::query()
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return array_map(fn (array $row): array => $this->present($row), $list);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRobot(RobotIdDto $dto): array
    {
        return $this->present($this->requireRobot($dto->getId())->getAttributes());
    }

    /**
     * @return array<string, mixed>
     */
    public function createRobot(CreateRobotDto $dto): array
    {
        $this->assertSuperUser();
        $name = $this->assertName($dto->getName());
        $this->assertNameUnique($name, null);
        $platform = $this->assertPlatform($dto->getPlatform());
        $webhook = $this->assertWebhook($dto->getWebhookUrl(), $platform);
        $secret = $this->normalizeSecret($dto->getSecret());

        $robot = new CronRobotEntity();
        $robot->setData([
            'name' => $name,
            'platform' => $platform,
            'webhook_url' => $webhook,
            'secret' => $secret,
            'status' => 1,
            'created_by' => $this->currentUserId(),
            'updated_by' => $this->currentUserId(),
        ]);
        $robot->save();

        return $this->present($robot->getAttributes());
    }

    /**
     * @return array<string, mixed>
     */
    public function updateRobot(UpdateRobotDto $dto): array
    {
        $this->assertSuperUser();
        $robot = $this->requireRobot($dto->getId());
        $name = $this->assertName($dto->getName());
        $this->assertNameUnique($name, $dto->getId());
        $platform = $this->assertPlatform($dto->getPlatform());

        $data = [
            'name' => $name,
            'platform' => $platform,
            'updated_by' => $this->currentUserId(),
        ];
        $webhook = trim($dto->getWebhookUrl());
        if ($webhook !== '' && !str_contains($webhook, '******')) {
            $data['webhook_url'] = $this->assertWebhook($webhook, $platform);
        } else {
            $this->assertWebhook((string) $robot->webhook_url, $platform);
        }
        $secret = $dto->getSecret();
        if ($secret !== '') {
            $data['secret'] = $this->normalizeSecret($secret);
        }

        $robot->setData($data);
        $robot->save();

        return $this->present($robot->getAttributes());
    }

    /**
     * @return array<string, mixed>
     */
    public function switchStatus(SwitchRobotStatusDto $dto): array
    {
        $this->assertSuperUser();
        $status = $dto->getStatus();
        if (!in_array($status, [0, 1], true)) {
            throw CronTaskException::throw('status 只能为 0 或 1', 422);
        }
        $robot = $this->requireRobot($dto->getId());
        $robot->setData([
            'status' => $status,
            'updated_by' => $this->currentUserId(),
        ]);
        $robot->save();

        return $this->present($robot->getAttributes());
    }

    public function deleteRobot(RobotIdDto $dto): int
    {
        $this->assertSuperUser();
        $id = $dto->getId();
        $robot = $this->requireRobot($id);
        $used = (int) CronAgentNodeGroupEntity::query()->where('robot_id', $id)->count();
        if ($used > 0) {
            throw CronTaskException::throw('该机器人被 ' . $used . ' 个节点组使用，请先在节点组中解除', 409);
        }
        $robot->delete();

        return $id;
    }

    /**
     * @return array{ok: bool, error: string}
     */
    public function testRobot(RobotIdDto $dto): array
    {
        $this->assertSuperUser();
        $robot = $this->requireRobot($dto->getId());
        $config = new RobotConfig(
            (int) $robot->id,
            (int) $robot->platform,
            (string) $robot->webhook_url,
            (string) $robot->secret,
        );
        $ok = false;
        $error = '';
        try {
            $result = $this->strategyFactory->make($config->platform)->send(
                $config,
                RobotAlertMessage::connectivityTest(),
            );
            $ok = $result->ok;
            $error = $ok ? '' : RobotWebhookMask::sanitizeError(
                $result->error !== '' ? $result->error : '测试发送失败',
                $config->webhookUrl,
                $config->secret,
            );
        } catch (\Throwable $e) {
            $error = RobotWebhookMask::sanitizeError($e->getMessage(), $config->webhookUrl, $config->secret);
        }

        $robot->setData([
            'last_test_at' => date('Y-m-d H:i:s'),
            'last_test_ok' => $ok ? 1 : 0,
            'last_test_error' => $error,
            'updated_by' => $this->currentUserId(),
        ]);
        $robot->save();

        return ['ok' => $ok, 'error' => $error];
    }

    public function requireEnabledRobot(int $robotId): CronRobotEntity
    {
        if ($robotId <= 0) {
            throw CronTaskException::throw('机器人不存在或未启用', 422);
        }
        $robot = (new CronRobotEntity())->loadById($robotId);
        if ($robot === null) {
            throw CronTaskException::throw('机器人不存在或未启用', 422);
        }
        if ((int) $robot->status !== 1) {
            throw CronTaskException::throw('机器人不存在或未启用', 422);
        }

        return $robot;
    }

    public function assertSuperUser(): void
    {
        $userId = $this->currentUserId();
        if ($userId <= 0 || !$this->staffUserService->isSuperUser($userId)) {
            throw new AuthException('无权限操作', 403);
        }
    }

    private function requireRobot(int $id): CronRobotEntity
    {
        if ($id <= 0) {
            throw CronTaskException::throw('id不能为空', -1);
        }
        $robot = (new CronRobotEntity())->loadById($id);
        if ($robot === null) {
            throw CronTaskException::throw('机器人不存在', -1);
        }

        return $robot;
    }

    private function assertName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw CronTaskException::throw('机器人名称不能为空', 422);
        }
        if (function_exists('mb_strlen') ? mb_strlen($name) > 100 : strlen($name) > 100) {
            throw CronTaskException::throw('机器人名称不能超过 100 字', 422);
        }

        return $name;
    }

    private function assertNameUnique(string $name, ?int $exceptId): void
    {
        $qb = CronRobotEntity::query()->where('name', $name);
        if ($exceptId !== null && $exceptId > 0) {
            $qb->where('id', '<>', $exceptId);
        }
        if ($qb->find()) {
            throw CronTaskException::throw('机器人名称已存在', 422);
        }
    }

    private function assertPlatform(int $platform): int
    {
        if (!RobotPlatform::isValid($platform)) {
            throw CronTaskException::throw('不支持的机器人平台', 422);
        }

        return $platform;
    }

    private function assertWebhook(string $url, int $platform): string
    {
        $url = trim($url);
        if ($url === '') {
            throw CronTaskException::throw('Webhook 地址不能为空', 422);
        }
        if (strlen($url) > 2048) {
            throw CronTaskException::throw('Webhook 地址过长', 422);
        }
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw CronTaskException::throw('Webhook 必须是 https 地址', 422);
        }
        $host = strtolower((string) $parts['host']);
        $allowed = RobotPlatform::webhookHosts($platform);
        if (!in_array($host, $allowed, true)) {
            throw CronTaskException::throw(
                RobotPlatform::label($platform) . ' Webhook 主机不合法',
                422,
            );
        }

        return $url;
    }

    private function normalizeSecret(string $secret): string
    {
        $secret = trim($secret);
        if (strlen($secret) > 512) {
            throw CronTaskException::throw('签名密钥过长', 422);
        }

        return $secret;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function present(array $row): array
    {
        $row['webhook_url_masked'] = RobotWebhookMask::maskUrl((string) ($row['webhook_url'] ?? ''));
        $row['secret_configured'] = trim((string) ($row['secret'] ?? '')) !== '';
        unset($row['webhook_url'], $row['secret'], $row['config_json']);

        return $row;
    }

    private function currentUserId(): int
    {
        return max(0, (int) (FrameworkContext::getUserId() ?? 0));
    }
}
