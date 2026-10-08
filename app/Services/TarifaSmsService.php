<?php

namespace App\Services;

use App\Models\TarifaSms;
use App\Models\Conta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TarifaSmsService
{
    /**
     * Encontrar a tarifa aplicável à compra.
     *
     * Prioridade:
     *
     * 1 - Tarifa exclusiva da conta
     * 2 - Tarifa geral
     */
    public function obterTarifa(int $contaId, int $quantidade): TarifaSms {

        if ($quantidade <= 0) {

            throw ValidationException::withMessages([
                'quantidade' =>
                    'A quantidade de SMS deve ser superior a zero.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 1. TARIFA EXCLUSIVA
        |--------------------------------------------------------------------------
        */
        $tarifa = $this->queryBase($quantidade)->where('conta_id', $contaId)->orderByDesc('quantidade_minima')->first();

        if ($tarifa) {
            return $tarifa;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. TARIFA GERAL
        |--------------------------------------------------------------------------
        */
        $tarifa = $this->queryBase($quantidade)->whereNull('conta_id')->orderByDesc('quantidade_minima')->first();

        if (!$tarifa) {

            throw ValidationException::withMessages([
                'quantidade' => 'Não existe tarifa configurada para esta quantidade de SMS.',
            ]);
        }

        return $tarifa;
    }

    /**
     * Condições comuns para tarifa geral/exclusiva.
     */
    private function queryBase(int $quantidade): Builder {

        $hoje = Carbon::today();

        return TarifaSms::query()
            ->where('activo', true)
            /*
             * quantidade >= mínima
             */
            ->where('quantidade_minima', '<=', $quantidade)
            /*
             * máxima NULL significa sem limite.
             */
            ->where(function ($query) use ($quantidade) {
                $query->whereNull('quantidade_maxima')
                    ->orWhere('quantidade_maxima','>=',$quantidade);
            })
            /*
             * Vigência inicial
             */
            ->where(function ($query) use ($hoje) {

                $query->whereNull('data_inicio')
                    ->orWhere('data_inicio','<=', $hoje);

            })

            /*
             * Vigência final
             */
            ->where(function ($query) use ($hoje) {
                $query->whereNull('data_fim')->orWhere('data_fim','>=',$hoje);
            });
    }

    /**
     * Calcular o valor da compra.
     */
    public function calcular(int $contaId, int $quantidade): array {

        $tarifa = $this->obterTarifa($contaId, $quantidade);
        $precoUnitario = (float) $tarifa->preco_sms;
        $valorTotal = round($quantidade * $precoUnitario,2);

        return [
            'tarifa' => $tarifa,
            'quantidade' => $quantidade,
            'preco_unitario' => $precoUnitario,
            'valor_total' => $valorTotal,
        ];
    }

    public function obterTarifaPosPago(Conta $conta): TarifaSms
    {
        if ($conta->tipo_cobranca !== 'POS_PAGO') {

            throw new RuntimeException(
                'A conta não é pós-paga.'
            );
        }

        if (!$conta->tarifa_sms_id) {

            throw new RuntimeException(
                'A conta pós-paga não possui tarifa configurada.'
            );
        }

        $tarifa = TarifaSms::query()
            ->where('id', $conta->tarifa_sms_id)
            ->where('activo', true)
            ->where(function ($query) {
                $query
                    ->whereNull('data_inicio')
                    ->orWhere(
                        'data_inicio',
                        '<=',
                        now()->toDateString()
                    );
            })
            ->where(function ($query) {
                $query
                    ->whereNull('data_fim')
                    ->orWhere(
                        'data_fim',
                        '>=',
                        now()->toDateString()
                    );
            })
            ->first();

        if (!$tarifa) {

            throw new RuntimeException(
                'A tarifa da conta pós-paga não está activa ou encontra-se fora da validade.'
            );
        }

        /*
        * Tarifa exclusiva.
        */
        if ($tarifa->conta_id !== null && (int) $tarifa->conta_id !== (int) $conta->id) {

            throw new RuntimeException(
                'A tarifa configurada não pertence a esta conta.'
            );
        }

        return $tarifa;
    }
}