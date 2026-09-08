<?php

declare(strict_types=1);

namespace App\WorkerCron;

use App\Module\Cron\Service\ExecutionService;
use Swoolefy\Worker\Cron\CronForkProcess;

class ScheduleForkCronProcess extends CronForkProcess
{
    public function run()
    {
        (new ExecutionService())->bootAgent();
        parent::run();
    }
}
