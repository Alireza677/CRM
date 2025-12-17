<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\SyncPendingSmsStatuses;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
        ->withSchedule(function (Schedule $schedule) {
        $schedule->command('reports:run-schedules')->everyFifteenMinutes();

        // Sync SMS delivery status periodically for pending messages
        $schedule->job(new SyncPendingSmsStatuses())->everyTenMinutes();

        // ✅ Lead SLA check (اگر می‌خواهی فعال باشد)
        $schedule->job(new \App\Jobs\CheckLeadSlaJob())
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // ✅ Mail sync (Gmail-style background sync)
        $schedule->command('mail:sync')
            ->everyFiveMinutes()      // اگر خواستی سریع‌تر: everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->name('mail-sync');
    })
    ->create();
