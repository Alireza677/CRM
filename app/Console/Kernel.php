<?php

namespace App\Console;

use App\Jobs\CheckLeadSlaJob;
use App\Jobs\SendLeadRotationWarningsJob;
use App\Jobs\SyncMailboxJob;
use App\Models\Mailbox;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * OUOU+ U.O¦O_ OñO UcOñOU+ƒ?OOªOO" O3UOO3O¦U.UO (php artisan schedule:run) U?OñOOrU^OU+UO U.UOƒ?OUcU+O_ O¦O OªOO"ƒ?OUØOUO OýU.OU+ƒ?OO"U+O_UOƒ?OO'O_UØ O®O"O¦ O'U^U+O_.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CheckLeadSlaJob())
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        $schedule->job(new SendLeadRotationWarningsJob())
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('mail:sync')
            ->everyFiveMinutes()       // UOO everyMinute() OU_Oñ OrU^OO3O¦UO O3OñUOO1ƒ?OO¦Oñ
            ->withoutOverlapping()
            ->runInBackground()
            ->name('mail-sync');
    }

    /**
     * OUOU+ U.O¦O_ UØU+U_OU. O"U^O¦ artisan U?OñOOrU^OU+UO U.UOƒ?OO'U^O_ O¦O O_O3O¦U^OñOO¦ UcU+O3U^U, U_OñU^U~UØ O"OOñU_OøOOñUO O'U^U+O_.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
