<?php

namespace App\Console;

use App\Jobs\CheckLeadSlaJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * این متد را کران‌جاب سیستمی (php artisan schedule:run) فراخوانی می‌کند تا جاب‌های زمان‌بندی‌شده ثبت شوند.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CheckLeadSlaJob())->everyFifteenMinutes();
    }

    /**
     * این متد هنگام بوت artisan فراخوانی می‌شود تا دستورات کنسول پروژه بارگذاری شوند.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
