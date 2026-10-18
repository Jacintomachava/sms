<?php

namespace App\Console\Commands;

use App\Models\TransacaoPagamento;
use App\Services\Pagamentos\ConfirmacaoPagamentoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconciliarCreditosSms extends Command
{
    protected $signature = 'pagamentos:reconciliar-creditos';

    protected $description = 'Recupera créditos SMS de pagamentos confirmados.';

    public function handle(
        ConfirmacaoPagamentoService $confirmacaoService
    ): int {
        $recuperados = 0;
        $falhados = 0;

        TransacaoPagamento::query()
            ->where('estado', 'SUCESSO')
            ->whereHas('compraSms', function ($query) {
                $query->whereNull('creditada_em');
            })
            ->orderBy('id')
            ->chunkById(100, function ($transacoes) use (
                $confirmacaoService,
                &$recuperados,
                &$falhados
            ) {
                foreach ($transacoes as $transacao) {
                    try {
                        $confirmacaoService->confirmar(
                            $transacao->id
                        );

                        $recuperados++;

                        Log::info(
                            'Crédito SMS reconciliado',
                            [
                                'transacao_id' => $transacao->id,
                                'compra_sms_id' =>
                                    $transacao->compra_sms_id,
                            ]
                        );
                    } catch (Throwable $e) {
                        $falhados++;

                        Log::error(
                            'Erro ao reconciliar crédito SMS',
                            [
                                'transacao_id' => $transacao->id,
                                'erro' => $e->getMessage(),
                            ]
                        );
                    }
                }
            });

        $this->info(
            "Recuperados: {$recuperados}; " .
            "Falhados: {$falhados}"
        );

        return self::SUCCESS;
    }
}
