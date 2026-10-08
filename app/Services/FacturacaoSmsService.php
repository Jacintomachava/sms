<?php

namespace App\Services;

use App\Models\CicloConsumoSms;
use App\Models\FacturaSms;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FacturacaoSmsService
{
    /**
     * Fecha um ciclo POS.
     */
    public function fecharCiclo(int $cicloId): CicloConsumoSms {

        return DB::transaction(function () use ($cicloId) {

            $ciclo = CicloConsumoSms::query()
                ->where('id', $cicloId)
                ->lockForUpdate()
                ->first();

            if (!$ciclo) {
                throw new RuntimeException(
                    'Ciclo de consumo não encontrado.'
                );
            }

            if ($ciclo->estado !== 'ABERTO') {

                throw new RuntimeException(
                    'Apenas ciclos abertos podem ser fechados.'
                );
            }

            /*
             * Não permitimos fechar um ciclo ainda em curso.
             *
             * Exemplo:
             * ciclo Setembro termina 30/09.
             * Pode ser fechado a partir de 01/10.
             */
            if (now()->startOfDay()->lte($ciclo->periodo_fim->copy()->endOfDay())) {

                throw new RuntimeException(
                    'O período deste ciclo ainda não terminou.'
                );
            }

            $ciclo->update([
                'estado' => 'FECHADO',
                'fechado_em' => now(),
            ]);

            return $ciclo->fresh();
        });
    }

    /**
     * Gera a factura de um ciclo fechado.
     */
    public function gerarFactura(int $cicloId, ?int $userId = null, int $diasVencimento = 15): FacturaSms {

        return DB::transaction(
            function () use (
                $cicloId,
                $userId,
                $diasVencimento
            ) {

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR CICLO
                |--------------------------------------------------------------------------
                */
                $ciclo = CicloConsumoSms::query()
                    ->where('id', $cicloId)
                    ->lockForUpdate()
                    ->first();

                if (!$ciclo) {

                    throw new RuntimeException(
                        'Ciclo de consumo não encontrado.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | SOMENTE CICLO FECHADO
                |--------------------------------------------------------------------------
                */
                if ($ciclo->estado !== 'FECHADO') {

                    throw new RuntimeException(
                        'Apenas ciclos fechados podem ser facturados.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | VERIFICAR DUPLICAÇÃO
                |--------------------------------------------------------------------------
                */
                $existente = FacturaSms::query()
                    ->where('ciclo_consumo_sms_id', $ciclo->id)
                    ->first();

                if ($existente) {

                    throw new RuntimeException(
                        'Este ciclo já possui uma factura.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | VALOR
                |--------------------------------------------------------------------------
                |
                | Usamos o valor do próprio ciclo.
                | A tarifa actual da conta NÃO é consultada.
                |
                */
                $subtotal = number_format(
                    (float) $ciclo->valor_estimado,
                    2,
                    '.',
                    ''
                );

                $valorTotal = $subtotal;

                /*
                |--------------------------------------------------------------------------
                | CRIAR FACTURA
                |--------------------------------------------------------------------------
                */
                $factura = FacturaSms::create([
                    'conta_id' => $ciclo->conta_id,
                    'ciclo_consumo_sms_id' => $ciclo->id,
                    /*
                     * Número temporário.
                     * Depois do INSERT temos o ID definitivo.
                     */
                    'numero' => 'TEMP-' . uniqid(),
                    'periodo_inicio' => $ciclo->periodo_inicio,
                    'periodo_fim' => $ciclo->periodo_fim,
                    'quantidade_sms' => $ciclo->quantidade_sms,
                    'segmentos' => $ciclo->segmentos,
                    'preco_unitario' => $ciclo->preco_unitario,
                    'subtotal' => $subtotal,
                    'valor_total' => $valorTotal,
                    'moeda' => $ciclo->moeda,
                    'data_emissao' => now()->toDateString(),
                    'data_vencimento' =>  now()->addDays($diasVencimento)->toDateString(),
                    'estado' => 'PENDENTE',
                    'criado_por' => $userId,
                ]);

                /*
                |--------------------------------------------------------------------------
                | NÚMERO DEFINITIVO
                |--------------------------------------------------------------------------
                |
                | Exemplo:
                | FT-2026-000001
                |
                */
                $numero = 'FT-' . now()->format('Y') . '-' . str_pad((string) $factura->id, 6, '0', STR_PAD_LEFT);

                $factura->update([
                    'numero' => $numero,
                ]);

                /*
                |--------------------------------------------------------------------------
                | CICLO FACTURADO
                |--------------------------------------------------------------------------
                */
                $ciclo->update([
                    'estado' => 'FACTURADO',
                ]);

                return $factura->fresh();
            }
        );
    }
}