<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use App\Console\Kernel as AppConsoleKernel;
use App\Jobs\SyncPendingSmsStatuses;
use App\Jobs\SendLeadRotationWarningsJob;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSingletons([
        ConsoleKernelContract::class => AppConsoleKernel::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        
    $middleware->alias([
        'signed.user' => \App\Http\Middleware\SignedUserToken::class,
    ]);

    // اگر aliasهای Spatie رو هم اینجا می‌خوای:
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})

    
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('reports:run-schedules')->everyFifteenMinutes();

        // Sync SMS delivery status periodically for pending messages
        $schedule->job(new SyncPendingSmsStatuses())->everyTenMinutes();

        // ƒo. Lead SLA check (OU_Oñ U.UOƒ?OOrU^OUØUO U?O1OU, O"OO'O_)
        $schedule->job(new \App\Jobs\CheckLeadSlaJob())
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        $schedule->job(new SendLeadRotationWarningsJob())
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // ƒo. Mail sync (Gmail-style background sync)
        $schedule->command('mail:sync')
            ->everyFiveMinutes()      // OU_Oñ OrU^OO3O¦UO O3OñUOO1ƒ?OO¦Oñ: everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->name('mail-sync');
    })
    ->create();
