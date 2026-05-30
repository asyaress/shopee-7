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
        if (!config('shopee.cron_enabled', false)) {
            return;
        }

        // Semua toko: produk + order + ads
        $schedule->command('shopee:sync-all-shops --days=2 --page_size=100')
            ->dailyAt('02:00')
            ->withoutOverlapping(120);

        $schedule->command('shopee:sync-all-shops --days=7 --page_size=100')
            ->weeklyOn(1, '03:00')
            ->withoutOverlapping(180);

        // Orders incremental tiap 30 menit (toko aktif / env)
        $schedule->command('shopee:sync-orders --days=2')
            ->everyThirtyMinutes()
            ->withoutOverlapping(30);

        $schedule->command('ceo:check-alerts')
            ->dailyAt('08:00');
            
        // $schedule->command('shopee:sync-orders --days=1')->everyMinute();


        // Kalau mau FULL harian:
        // $schedule->command('shopee:sync-all --days=7 --page_size=100')
        //     ->dailyAt('02:00')
        //     ->withoutOverlapping(120); // lock 120 menit
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Register custom commands.
     */
    protected $commands = [
        \App\Console\Commands\ShopeeSyncOrdersCommand::class,
        \App\Console\Commands\ShopeeSyncProductsCommand::class,
        \App\Console\Commands\ShopeeSyncAllCommand::class,
        \App\Console\Commands\ShopeeSyncAdsCommand::class,
        \App\Console\Commands\ShopeeSyncAllShopsCommand::class,
        \App\Console\Commands\ShopeeSyncBcgCommand::class,
        \App\Console\Commands\CeoCheckAlertsCommand::class,
    ];
}
