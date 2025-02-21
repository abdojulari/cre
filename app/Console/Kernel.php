<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ProcessDataCommand::class,
    ];

    // Rest of the class code...

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('statistics:export')
                ->dailyAt('00:45')
                ->withoutOverlapping()
                ->runInBackground();
    }
}
