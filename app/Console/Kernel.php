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
        /*
        |--------------------------------------------------------------------------
        | RECONCILIAÇÃO AUTOMÁTICA DE PAGAMENTOS
        |--------------------------------------------------------------------------
        |
        | Executa a cada minuto.
        | Recupera pagamentos confirmados sem crédito na carteira.
        |
        */
        $schedule->command('pagamentos:reconciliar-creditos')->everyMinute()->withoutOverlapping(10);

        /*
        |--------------------------------------------------------------------------
        | RECONCILIAÇÃO AUTOMÁTICA SMPP
        |--------------------------------------------------------------------------
        |
        | Executa a cada 5 minutos.
        | Reconcilia mensagens pendentes com a Movitel.
        |
        */
        $schedule->command('smpp:reconciliar --limite=100')->everyFiveMinutes()->withoutOverlapping(10)->appendOutputTo(storage_path('logs/smpp-reconciliacao.log'));

        /*
        |--------------------------------------------------------------------------
        | FACTURAÇÃO AUTOMÁTICA PÓS-PAGO
        |--------------------------------------------------------------------------
        |
        | Executa diariamente às 00h30, horário de Moçambique.
        |
        | 1. Procura ciclos de meses anteriores.
        | 2. Ignora ciclos com SMS pendentes.
        | 3. Fecha os ciclos elegíveis.
        | 4. Emite as respectivas facturas.
        |
        */
        $schedule->command('sms:facturar-ciclos')->dailyAt('00:30')->timezone('Africa/Maputo')->withoutOverlapping(120)->appendOutputTo(storage_path('logs/facturacao-sms.log'));

        $schedule->command('sms:processar-multas')->dailyAt('01:00')->timezone('Africa/Maputo')->withoutOverlapping();
        
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