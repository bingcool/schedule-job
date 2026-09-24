<?php

declare(strict_types=1);

namespace App\Module\Cron\Mapper;

use InterfaceApi\ScheduleJob\App\Module\Cron\Dto\CronRobot\CronRobotRowDto;

final class CronRobotRowMapper
{
    /**
         * @param array<string, mixed> $row
         */
        public static function fromEntityRow(array $row): CronRobotRowDto
        {
            $dto = new CronRobotRowDto();
            $dto->setId((int) ($row['id'] ?? 0));
            $dto->setName((string) ($row['name'] ?? ''));
            $dto->setPlatform((int) ($row['platform'] ?? 0));
            $dto->setWebhookUrl((string) ($row['webhook_url_masked'] ?? ''));
            $dto->setSecretConfigured((bool) ($row['secret_configured'] ?? false));
            $dto->setStatus((int) ($row['status'] ?? 0));
            $dto->setLastTestAt((string) ($row['last_test_at'] ?? ''));
            $dto->setLastTestOk((int) ($row['last_test_ok'] ?? 0));
            $dto->setLastTestError((string) ($row['last_test_error'] ?? ''));
            $dto->setCreatedAt((string) ($row['created_at'] ?? ''));
            $dto->setUpdatedAt((string) ($row['updated_at'] ?? ''));

            return $dto;
        }
}
