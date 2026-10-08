<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\SenderId;
use App\Models\Carteira;
use App\Models\Conta;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsService
{
    public function __construct(
        private SmsSegmentService $segmentService,
        private CarteiraService $carteiraService,
        private TarifaSmsService $tarifaSmsService,
        private ConsumoPosPagoService $consumoPosPagoService,
        private LimiteInternoSmsService $limiteInternoSmsService,
        private LimiteSmsService $limiteSmsService,
        private StockSmsService $stockSmsService
    ) {
    }

    public function enviar(int $contaId, int $senderId, string $telefone, string $mensagem, string $origem, ?int $userId = null): Sms {

        /*
        |--------------------------------------------------------------------------
        | CONTA
        |--------------------------------------------------------------------------
        */
        $conta = Conta::query() ->where('id', $contaId)->where('estado', 'ACTIVA')->first();

        if (!$conta) {

            throw new RuntimeException(
                'Conta inválida ou inactiva.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TELEFONE
        |--------------------------------------------------------------------------
        */
        $telefone = $this->normalizarTelefone($telefone);

        /*
        |--------------------------------------------------------------------------
        | SENDER
        |--------------------------------------------------------------------------
        */
        $sender = $this->obterSenderValido($contaId, $senderId);

        /*
        |--------------------------------------------------------------------------
        | SEGMENTOS
        |--------------------------------------------------------------------------
        */
        $calculo = $this->segmentService->calcular($mensagem);

        /*
        |--------------------------------------------------------------------------
        | STOCK FÍSICO
        |--------------------------------------------------------------------------
        */
        if (!$this->stockSmsService->temStock($calculo['segmentos'])) {

            throw new RuntimeException(
                'Stock SMS temporariamente indisponível.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAÇÕES POR TIPO DE COBRANÇA
        |--------------------------------------------------------------------------
        */
        $tarifaPosPago = null;

        if ($conta->tipo_cobranca === 'PRE_PAGO') {

            $carteira = Carteira::query() ->where('conta_id', $contaId)->first();

            if (!$carteira) {

                throw new RuntimeException(
                    'Carteira não encontrada.'
                );
            }

            if ( $carteira->saldo_sms < $calculo['segmentos']) {

                throw new RuntimeException(
                    'Saldo SMS insuficiente.'
                );
            }

        }
        elseif ($conta->tipo_cobranca === 'POS_PAGO') {

            /*
            |--------------------------------------------------------------------------
            | TARIFA POS-PAGO
            |--------------------------------------------------------------------------
            */
            $tarifaPosPago = $this->tarifaSmsService->obterTarifaPosPago($conta);

        }
        else {

            throw new RuntimeException(
                'Tipo de cobrança inválido.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */
        return DB::transaction(
            function () use (
                $conta,
                $contaId,
                $sender,
                $telefone,
                $mensagem,
                $calculo,
                $userId,
                $origem,
                $tarifaPosPago
            ) {

                /*
                |--------------------------------------------------------------------------
                | PROTECÇÃO POS-PAGO
                |--------------------------------------------------------------------------
                */
                if ($conta->tipo_cobranca === 'POS_PAGO') {

                    $conta = Conta::query()
                        ->where('id', $contaId)
                        ->where('estado', 'ACTIVA')
                        ->lockForUpdate()
                        ->first();

                    if (!$conta) {
                        throw new RuntimeException(
                            'Conta inválida ou inactiva.'
                        );
                    }

                    /*
                    | Limite interno INFORDATA.
                    */
                    $this->limiteInternoSmsService->validarEnvio($conta, $calculo['segmentos']);

                    /*
                    | Limite opcional definido pelo cliente.
                    */
                    $this->limiteSmsService->validarEnvio($conta, $calculo['segmentos']);
                    
                }

                /*
                |--------------------------------------------------------------------------
                | SMS
                |--------------------------------------------------------------------------
                */
                $sms = Sms::create([
                    'conta_id' => $contaId,
                    'sender_id' => $sender->id,
                    'telefone' => $telefone,
                    'mensagem' => $mensagem,
                    'encoding' => $calculo['encoding'],
                    'caracteres' => $calculo['caracteres'],
                    'segmentos' => $calculo['segmentos'],
                    'estado' => 'ENVIADO',
                    'provider' => 'MOVITEL',
                    'origem' => $origem,
                    'criado_por' => $userId,
                    'enviado_em' => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | STOCK FÍSICO INFORDATA
                |--------------------------------------------------------------------------
                */
                $this->stockSmsService
                    ->consumirDentroDaTransacao(
                        smsId: $sms->id,
                        segmentos: $calculo['segmentos'],
                        userId: $userId
                    );

                /*
                |--------------------------------------------------------------------------
                | PRE-PAGO
                |--------------------------------------------------------------------------
                */
                if ($conta->tipo_cobranca === 'PRE_PAGO') {

                    $this->carteiraService
                        ->debitarSms(
                            contaId: $contaId,
                            quantidade: $calculo['segmentos'],
                            origem: 'SMS',
                            referencia: 'SMS-' . $sms->id,
                            descricao: 'Envio de SMS para ' .$telefone,
                            userId: $userId,

                            metadata: [
                                'sms_id' => $sms->id,
                                'sender_id' => $sender->id,
                                'telefone' => $telefone,
                                'segmentos' => $calculo['segmentos'],
                            ],

                            smsId: $sms->id
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | POS-PAGO
                |--------------------------------------------------------------------------
                */
                if ($conta->tipo_cobranca === 'POS_PAGO') {

                    $this->consumoPosPagoService
                        ->registar(
                            conta: $conta,
                            tarifa: $tarifaPosPago,
                            segmentos: $calculo['segmentos']
                        );
                }

                return $sms;
            }
        );
    }

    private function obterSenderValido(int $contaId, int $senderId): SenderId {

        $sender = SenderId::query()

            /*
            * Sender solicitado.
            */
            ->where('id', $senderId)

            /*
            * Tem de estar aprovado pela plataforma/operadora.
            */
            ->where('estado', 'APROVADO')

            /*
            * Tem de estar atribuído à conta
            * e a associação tem de estar activa.
            */
            ->whereHas(
                'contas',
                function ($query) use ($contaId) {

                    $query
                        ->where('contas.id', $contaId)
                        ->where('conta_sender_ids.estado','ACTIVO');
                }
            )

            ->first();


        if (!$sender) {

            throw new RuntimeException(
                'Sender ID inválido, não aprovado ou não autorizado para esta conta.'
            );
        }

        return $sender;
    }

    private function normalizarTelefone(string $telefone): string {

        $telefone = preg_replace('/\D+/','',$telefone);

        if (strlen($telefone) === 12 && str_starts_with($telefone, '258')) {

            return $telefone;
        }

        if (strlen($telefone) === 9) {
            return '258' . $telefone;
        }

        throw new RuntimeException(
            'Número de telefone inválido.'
        );
    }
}