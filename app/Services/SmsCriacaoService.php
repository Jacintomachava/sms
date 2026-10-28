<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\SmsOutbox;
use App\Services\SMPP\SmsReservaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SmsCriacaoService
{
    public function __construct(
        private SmsReservaService $reservaService
    ) {
    }

    /**
     * Criar SMS, reservar créditos e criar outbox.
     *
     * Não envia mensagens à operadora.
     */
    public function criar(
        int $contaId,
        int $senderId,
        string $telefone,
        string $mensagem,
        ?int $userId = null,
        string $origem = 'PAINEL'
    ): Sms {

        $telefone = trim($telefone);
        $mensagem = trim($mensagem);

        if (!in_array($origem, ['PAINEL', 'API', 'CAMPANHA'], true)) {
            throw new RuntimeException('Origem da SMS inválida.');
        }

        if (!preg_match('/^(?:\+?258)?(82|83|84|85|86|87|88)[0-9]{7}$/', $telefone)) {
            throw ValidationException::withMessages([
                'telefone' => 'Número de telefone moçambicano inválido.',
            ]);
        }

        // Guardar sempre no formato nacional.
        $telefone = substr(preg_replace('/\D/', '', $telefone), -9);

        if ($mensagem === '') {
            throw ValidationException::withMessages([
                'mensagem' => 'A mensagem é obrigatória.',
            ]);
        }

        /*
         * Cálculo conservador de segmentos.
         *
         * GSM7 básico: 160 caracteres no primeiro
         * segmento e 153 nos seguintes.
         *
         * Unicode: 70 no primeiro segmento
         * e 67 nos seguintes.
         *
         * A validação definitiva deve usar o
         * mesmo codificador do SmppService.
         */
        $gsm7 = preg_match('/^[A-Za-z0-9 @£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ!"#¤%&\'()*+,\-.\/:;<=>?¡ÄÖÑÜ§¿äöñüà\r\n]*$/u', $mensagem) === 1;

        $caracteres = mb_strlen($mensagem, 'UTF-8');

        $encoding = $gsm7 ? 'GSM7' : 'UNICODE';

        if ($gsm7) {

            $segmentos = $caracteres <= 160 ? 1 : (int) ceil($caracteres / 153);

        } else {

            $unidadesUtf16 = strlen(mb_convert_encoding($mensagem, 'UTF-16LE', 'UTF-8')) / 2;
            $segmentos = $unidadesUtf16 <= 70 ? 1 : (int) ceil($unidadesUtf16 / 67);

        }

        if ($segmentos < 1 || $segmentos > 10) {
            throw ValidationException::withMessages([
                'mensagem' =>
                    'A mensagem ultrapassa o limite de 10 segmentos.',
            ]);
        }

        return DB::transaction(function () use (
            $contaId,
            $senderId,
            $telefone,
            $mensagem,
            $encoding,
            $caracteres,
            $segmentos,
            $userId,
            $origem
        ) {
            /*
             * 1. Validar conta e remetente.
             */
            $conta = DB::table('contas')->where('id', $contaId)->first();

            if (!$conta) {
                throw new RuntimeException(
                    'Conta não encontrada.'
                );
            }

            if (!in_array($conta->tipo_cobranca, ['PRE_PAGO', 'POS_PAGO'], true)) {
                throw new RuntimeException('Tipo de cobrança não suportado.');
            }

            /*
             * A autorização do sender deve ser
             * validada pelo módulo de remetentes,
             * antes de chamar este serviço.
             *
             * Ainda precisamos de confirmar a
             * estrutura das tabelas de aprovação
             * e associação de senders às contas.
             */
            if ($senderId <= 0) {
                throw new RuntimeException(
                    'Remetente inválido.'
                );
            }

            /*
             * 2. Criar SMS.
             */
            $sms = new Sms();

            $sms->conta_id = $contaId;
            $sms->sender_id = $senderId;
            $sms->telefone = $telefone;
            $sms->mensagem = $mensagem;
            $sms->encoding = $encoding;
            $sms->caracteres = $caracteres;
            $sms->segmentos = $segmentos;

            $sms->finalidade = 'NORMAL';
            $sms->custeado_por = 'CLIENTE';
            $sms->facturavel_cliente = true;

            $sms->origem = $origem;
            $sms->estado = 'PENDENTE';
            $sms->provider = 'MOVITEL';
            $sms->criado_por = $userId;

            $sms->save();

            /*
             * 3. Reservar carteira, lotes FIFO
             * e stock da Movitel.
             *
             * Se falhar, a criação da SMS também
             * será revertida.
             */
            $this->reservaService->reservar($sms->id);

            /*
             * 4. Criar outbox.
             *
             * Não publicar na fila Redis nesta fase.
             */
            SmsOutbox::create([
                'sms_id' => $sms->id,
                'estado' => 'PENDENTE',
                'tentativas_publicacao' => 0,
            ]);

            return $sms;

        }, 3);
    }
}
