<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('peminjaman:update-status')->everyMinute();
        $schedule->command('peminjaman:kirimpesan')->dailyAt('18:34');
    }

    protected $commands = [
        Commands\PerbaruiStatusPeminjaman::class,
        Commands\KirimPesanPengingat::class,
    ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
