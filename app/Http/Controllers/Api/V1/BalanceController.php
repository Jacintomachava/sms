<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Carteira;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    public function show(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | CONTA IDENTIFICADA PELA API KEY
        |--------------------------------------------------------------------------
        */
        $conta = $request->attributes->get('conta');

        if (!$conta) {
            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível identificar a conta.',
                'error' => [
                    'code' => 'ACCOUNT_NOT_FOUND',
                ],
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | PRE-PAGO
        |--------------------------------------------------------------------------
        */
        if ($conta->tipo_cobranca === 'PRE_PAGO') {

            $carteira = Carteira::query()
                ->where('conta_id', $conta->id)
                ->first();

            return response()->json([
                'status' => 1,
                'message' => 'Saldo consultado com sucesso.',
                'data' => [
                    'billing_type' => 'PREPAID',
                    'balance' => (int) ($carteira?->saldo_sms ?? 0),
                    'unit' => 'SMS',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR MODALIDADE
        |--------------------------------------------------------------------------
        */
        if ($conta->tipo_cobranca !== 'POS_PAGO') {
            return response()->json([
                'status' => 0,
                'message' => 'Modalidade de cobrança inválida.',
                'error' => [
                    'code' => 'INVALID_BILLING_TYPE',
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CICLO PÓS-PAGO ACTUAL
        |--------------------------------------------------------------------------
        | Procuramos um ciclo que contenha a data actual.
        |--------------------------------------------------------------------------
        */
        $hoje = Carbon::today();

        $ciclo = DB::table('ciclos_consumo_sms')
            ->where('conta_id', $conta->id)
            ->whereDate('periodo_inicio', '<=', $hoje->toDateString())
            ->whereDate('periodo_fim', '>=', $hoje->toDateString())
            ->orderByDesc('periodo_inicio')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | LIMITE DO CLIENTE
        |--------------------------------------------------------------------------
        | Seleccionamos a configuração activa mais recente.
        |--------------------------------------------------------------------------
        */
        $limiteCliente = DB::table('conta_limites_sms')
            ->where('conta_id', $conta->id)
            ->where('activo', 1)
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | LIMITE INTERNO INFORDATA
        |--------------------------------------------------------------------------
        */
        $limiteInterno = DB::table('conta_limites_internos_sms')
            ->where('conta_id', $conta->id)
            ->where('activo', 1)
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CONSUMO
        |--------------------------------------------------------------------------
        */
        $consumido = (int) ($ciclo?->segmentos ?? 0);
        $reservado = (int) ($ciclo?->segmentos_reservados ?? 0);

        $totalComprometido = $consumido + $reservado;

        /*
        |--------------------------------------------------------------------------
        | LIMITES
        |--------------------------------------------------------------------------
        */
        $limiteClienteSms = $limiteCliente
            ? (int) $limiteCliente->limite_sms
            : null;

        $limiteInternoSms = $limiteInterno
            ? (int) $limiteInterno->limite_sms
            : null;

        /*
        |--------------------------------------------------------------------------
        | LIMITE EFECTIVO
        |--------------------------------------------------------------------------
        | Quando ambos estão configurados, aplicamos o menor.
        |--------------------------------------------------------------------------
        */
        $limites = array_values(array_filter(
            [$limiteClienteSms, $limiteInternoSms],
            fn ($valor) => $valor !== null
        ));

        $limiteEfectivo = count($limites) > 0
            ? min($limites)
            : null;

        $disponivel = $limiteEfectivo !== null
            ? max(0, $limiteEfectivo - $totalComprometido)
            : null;

        /*
        |--------------------------------------------------------------------------
        | RESPOSTA
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => 1,
            'message' => 'Consumo pós-pago consultado com sucesso.',
            'data' => [
                'billing_type' => 'POSTPAID',

                'period' => $ciclo
                    ? [
                        'start' => $ciclo->periodo_inicio,
                        'end' => $ciclo->periodo_fim,
                    ]
                    : null,

                'consumed' => $consumido,
                'reserved' => $reservado,
                'committed' => $totalComprometido,

                'limit' => $limiteEfectivo,
                'remaining' => $disponivel,

                'unit' => 'SMS',
            ],
        ]);
    }
}
