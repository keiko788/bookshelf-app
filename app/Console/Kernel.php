<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * アプリケーションのコマンドスケジュールを定義する。
     *
     * @param  Schedule  $schedule  スケジュール
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reading-plans:update-expired')
            ->daily();

        $schedule->command('reading-plans:send-reminders')
            ->dailyAt('20:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
