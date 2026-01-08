<?php

namespace App\Console\Commands;

use App\Jobs\SyncNovatelCdrJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncNovatelCdr extends Command
{
    protected $signature = 'novatel:cdr-sync
        {--from= : Start datetime (Y-m-d H:i:s)}
        {--to= : End datetime (Y-m-d H:i:s)}
        {--minutes= : Lookback window in minutes}
        {--destination= : Destination filter}
        {--queue-id= : Queue ID filter}
        {--call-type= : Call type filter}';

    protected $description = 'Sync Novatel CDR logs into phone_calls';

    public function handle(): int
    {
        $fromOption = $this->option('from');
        $toOption = $this->option('to');
        $minutesOption = $this->option('minutes');

        $from = $fromOption ? Carbon::parse($fromOption)->toDateTimeString() : null;
        $to = $toOption ? Carbon::parse($toOption)->toDateTimeString() : null;
        $minutes = $minutesOption !== null ? (int) $minutesOption : null;

        SyncNovatelCdrJob::dispatch(
            $from,
            $to,
            $minutes,
            $this->option('destination'),
            $this->option('queue-id'),
            $this->option('call-type')
        );

        $this->info('Novatel CDR sync job dispatched.');

        return self::SUCCESS;
    }
}
