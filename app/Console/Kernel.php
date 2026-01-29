<?php

namespace App\Console;

use App\Jobs\CheckLeadSlaJob;
use App\Jobs\SendLeadRotationWarningsJob;
use App\Jobs\SyncMailboxJob;
use App\Models\Mailbox;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Morilog\Jalali\Jalalian;

class Kernel extends ConsoleKernel
{
    
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CheckLeadSlaJob())
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        $schedule->job(new SendLeadRotationWarningsJob())
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('mail:sync')
            ->everyFiveMinutes()       
            ->withoutOverlapping()
            ->runInBackground()
            ->name('mail-sync');

        $schedule->command('activities:send-reminders')
            ->everyMinute()
            ->withoutOverlapping()
            ->name('activities-send-reminders');

        if (config('services.novatel.token') && config('services.novatel.tenant')) {
            $schedule->command('novatel:cdr-sync --minutes=' . (int) config('services.novatel.cdr_default_minutes', 10))
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->name('novatel-cdr-sync');
        }

        $jalaliNow = Jalalian::now();
        $currentJalaliYear = $jalaliNow->getYear();
        $nextJalaliYear = $currentJalaliYear + 1;

        $schedule->command("holidays:import {$currentJalaliYear}")
            ->monthlyOn(1, '03:10')
            ->withoutOverlapping()
            ->name('holidays-import-current');

        $schedule->command("holidays:import {$nextJalaliYear}")
            ->monthlyOn(1, '03:20')
            ->when(fn () => $jalaliNow->getMonth() >= 11)
            ->withoutOverlapping()
            ->name('holidays-import-next');
    }

   
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
