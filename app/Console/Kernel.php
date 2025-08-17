<?php

namespace App\Console;

use App\Console\Commands\DBProduk;
use App\Models\Produk;
use App\Models\BackupProduk;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        DBProduk::class
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule to run on the 1st day of each month at 00:00
        // $schedule->command('db:produk')->monthlyOn(1, '00:00');
        $schedule->command('db:produk')->everyMinute();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
