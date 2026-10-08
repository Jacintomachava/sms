<?php

namespace App\Services;

use App\Models\CompraSms;
use App\Models\Conta;
use App\Models\Carteira;
use App\Models\CarteiraLoteSms;
use App\Models\CarteiraMovimento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


use RuntimeException;

class CompraSmsService
{
    public function __construct(private TarifaSmsService $tarifaService) {
    }

    public function criar(int $contaId, int $quantidade, ?int $userId = null): CompraSms {

        $conta = Conta::findOrFail($contaId);

        /*
         * Compra de créditos apenas PRE_PAGO.
         */
        if ($conta->tipo_cobranca !== 'PRE_PAGO') {

            throw ValidationException::withMessages([
                'quantidade' => 'A compra de créditos SMS está disponível apenas para contas pré-pagas.',
            ]);
        }

        $calculo = $this->tarifaService->calcular($contaId,$quantidade);

        return CompraSms::create([
            'conta_id' => $contaId,
            'tarifa_sms_id' => $calculo['tarifa']->id,
            'quantidade_sms' => $quantidade,
            /*
             * Snapshot.
             */
            'preco_unitario' => $calculo['preco_unitario'],
            'valor_total' => $calculo['valor_total'],
            'moeda' => 'MZN',
            'estado' => 'PENDENTE',
            'criado_por' => $userId,
        ]);
    }


}