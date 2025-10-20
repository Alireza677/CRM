<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Reports\ScheduleRunner;

class RunReportSchedules extends Command
{
    protected $signature = 'reports:run-schedules';
    protected $description = 'Execute due report schedules and email exports';

    public function handle(ScheduleRunner $runner): int
    {
        $this->info('Running report schedules...');
        $runner->runDue();
        $this->info('Done.');
        return self::SUCCESS;
    }
}

