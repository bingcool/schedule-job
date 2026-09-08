<?php

declare(strict_types=1);

namespace App\WorkerCron;

use App\Module\Cron\Service\ExecutionService;
use Swoolefy\Worker\Cron\CronUrlProcess;

class ScheduleUrlCronProcess extends CronUrlProcess
{
    public function run()
    {
        (new ExecutionService())->bootAgent();
        parent::run();
    }
}
