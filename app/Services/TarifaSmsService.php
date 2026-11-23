<?php

namespace App\Services;

use App\Models\TarifaSms;
use App\Models\Conta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TarifaSmsService
{
    /**
     * Encontrar a tarifa aplicável à compra.
     *
     * REGRA:
     * 1. Se contas.tarifa_sms_id estiver preenchido,
     *    usar exclusivamente essa tarifa.
     *
     * 2. Se contas.tarifa_sms_id for NULL,
     *    usar os escalões gerais pela quantidade.
     */
    public function obterTarifa(
        int $contaId,
        int $quantidade
    ): TarifaSms {

        if ($quantidade <= 0) {
            throw ValidationException::withMessages([
                'quantidade' =>
                    'A quantidade de SMS deve ser superior a zero.',
            ]);
        }

        $conta = Conta::query()->findOrFail($contaId);

        /*
        |--------------------------------------------------------------------------
        | 1. TARIFA FIXA ASSOCIADA À CONTA
        |--------------------------------------------------------------------------
        */
        if ($conta->tarifa_sms_id !== null) {

            $tarifa = $this->queryVigente()
                ->whereKey($conta->tarifa_sms_id)
                ->first();

            if (!$tarifa) {
                throw ValidationException::withMessages([
                    'tarifa' =>
                        'A tarifa configurada para esta conta não está activa ou está fora da validade.',
                ]);
            }

            /*
             * Não permitir utilizar a tarifa exclusiva
             * pertencente a outra conta.
             */
            if (
                $tarifa->conta_id !== null &&
                (int) $tarifa->conta_id !== (int) $contaId
            ) {
                throw ValidationException::withMessages([
                    'tarifa' =>
                        'A tarifa configurada não pertence a esta conta.',
                ]);
            }

            /*
             * Tarifa fixa:
             * não aplicar quantidade_minima
             * nem quantidade_maxima.
             */
            return $tarifa;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. TARIFAS GERAIS POR ESCALÃO
        |--------------------------------------------------------------------------
        */
        $tarifa = $this->queryBase($quantidade)
            ->whereNull('conta_id')
            ->orderByDesc('quantidade_minima')
            ->first();

        if (!$tarifa) {
            throw ValidationException::withMessages([
                'quantidade' =>
                    'Não existe tarifa geral configurada para esta quantidade de SMS.',
            ]);
        }

        return $tarifa;
    }

    /**
     * Condições comuns de actividade e vigência.
     */
    private function queryVigente(): Builder
    {
        $hoje = Carbon::today()->toDateString();

        return TarifaSms::query()
            ->where('activo', true)
            ->where(function ($query) use ($hoje) {
                $query->whereNull('data_inicio')
                    ->orWhere('data_inicio', '<=', $hoje);
            })
            ->where(function ($query) use ($hoje) {
                $query->whereNull('data_fim')
                    ->orWhere('data_fim', '>=', $hoje);
            });
    }

    /**
     * Tarifas gerais por quantidade.
     */
    private function queryBase(int $quantidade): Builder
    {
        return $this->queryVigente()
            ->where('quantidade_minima', '<=', $quantidade)
            ->where(function ($query) use ($quantidade) {
                $query->whereNull('quantidade_maxima')
                    ->orWhere(
                        'quantidade_maxima',
                        '>=',
                        $quantidade
                    );
            });
    }

    /**
     * Calcular o valor da compra.
     */
    public function calcular(
        int $contaId,
        int $quantidade
    ): array {

        $tarifa = $this->obterTarifa(
            $contaId,
            $quantidade
        );

        $precoUnitario = (float) $tarifa->preco_sms;

        $valorTotal = round(
            $quantidade * $precoUnitario,
            2
        );

        return [
            'tarifa' => $tarifa,
            'quantidade' => $quantidade,
            'preco_unitario' => $precoUnitario,
            'valor_total' => $valorTotal,
        ];
    }

    /**
     * Obter tarifa para facturação pós-paga.
     */
    public function obterTarifaPosPago(Conta $conta): TarifaSms
    {
        if ($conta->tipo_cobranca !== 'POS_PAGO') {
            throw new RuntimeException(
                'A conta não é pós-paga.'
            );
        }

        if ($conta->tarifa_sms_id === null) {
            throw new RuntimeException(
                'A conta pós-paga não possui tarifa configurada.'
            );
        }

        $tarifa = $this->queryVigente()
            ->whereKey($conta->tarifa_sms_id)
            ->first();

        if (!$tarifa) {
            throw new RuntimeException(
                'A tarifa da conta pós-paga não está activa ou encontra-se fora da validade.'
            );
        }

        if (
            $tarifa->conta_id !== null &&
            (int) $tarifa->conta_id !== (int) $conta->id
        ) {
            throw new RuntimeException(
                'A tarifa configurada não pertence a esta conta.'
            );
        }

        return $tarifa;
    }
}
