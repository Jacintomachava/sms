<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\SenderId;
use App\Models\Carteira;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsService
{
    public function __construct(private SmsSegmentService $segmentService, private CarteiraService $carteiraService) {
    }

    public function enviar(int $contaId, int $senderId, string $telefone, string $mensagem, ?int $userId = null, string $origem): Sms {

        $telefone = $this->normalizarTelefone($telefone);

        /*
        |--------------------------------------------------------------------------
        | VALIDAR SENDER
        |--------------------------------------------------------------------------
        */
        $sender = $this->obterSenderValido($contaId, $senderId);

        /*
        |--------------------------------------------------------------------------
        | CALCULAR SEGMENTOS
        |--------------------------------------------------------------------------
        */
        $calculo = $this->segmentService->calcular($mensagem);


        /*
        |--------------------------------------------------------------------------
        | VALIDAR SALDO
        |--------------------------------------------------------------------------
        */
        $carteira = Carteira::where('conta_id', $contaId)->first();

        if (!$carteira) {
            throw new RuntimeException(
                'Carteira não encontrada.'
            );
        }

        if ($carteira->saldo_sms < $calculo['segmentos']) {
            throw new RuntimeException(
                'Saldo SMS insuficiente.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CRIAR SMS
        |--------------------------------------------------------------------------
        */
        return DB::transaction(function () use (
            $contaId,
            $sender,
            $telefone,
            $mensagem,
            $calculo,
            $userId,
            $origem
        ) {

            $sms = Sms::create([
                'conta_id' => $contaId,
                'sender_id' => $sender->id,
                'telefone' => $telefone,
                'mensagem' => $mensagem,
                'encoding' => $calculo['encoding'],
                'caracteres' => $calculo['caracteres'],
                'segmentos' => $calculo['segmentos'],
                'estado' => 'ENVIADO',
                //'estado' => 'PENDENTE',
                'provider' => 'MOVITEL',
                'origem'  => $origem,
                'criado_por' => $userId,
                'enviado_em' => now(),
            ]);

            /*
             * Debitar os créditos.
             */
            $this->carteiraService->debitarSms(
                contaId: $contaId,
                quantidade: $calculo['segmentos'],
                origem: 'SMS',
                referencia: 'SMS-' . $sms->id,
                descricao: 'Envio de SMS para '. $telefone,
                userId: $userId,

                metadata: [
                    'sms_id' => $sms->id,
                    'sender_id' => $sender->id,
                    'telefone' => $telefone,
                    'segmentos' => $calculo['segmentos'],
                ]
            );


            return $sms;
        });
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