<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Carteira;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function show(Request $request)
    {
        $conta = $request ->attributes ->get('conta');

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
         * PRE-PAGO
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
                    'balance' => (int) ( $carteira ?->saldo_sms ?? 0 ),
                    'unit' => 'SMS',
                ],
            ]);
        }

        /*
         * POS-PAGO ainda será implementado.
         *
         * Não devolvemos dados fictícios.
         */
        return response()->json([
            'status' => 0,
            'message' => 'A consulta de consumo pós-pago ainda não está disponível.',
            'error' => [
                'code' => 'POSTPAID_NOT_AVAILABLE',
            ],

        ], 501);
    }
}