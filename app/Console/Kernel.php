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

        /*
        |--------------------------------------------------------------------------
        | Reconciliação automática de pagamentos SMS
        |--------------------------------------------------------------------------
        |
        | Executa a cada minuto.
        | Recupera pagamentos confirmados sem crédito na carteira.
        |
        */
        $schedule->command('pagamentos:reconciliar-creditos')->everyMinute()->withoutOverlapping(10);
        
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
