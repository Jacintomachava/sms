<?php

namespace App\Console\Commands;

use App\Models\Sms;
use App\Services\SMPP\SmppConnectionManager;
use App\Services\SMPP\SmppDispatcher;
use App\Services\SMPP\SmppEnvioService;
use App\Services\SMPP\SmppRateLimiter;
use App\Services\SMPP\SmppRecuperacaoService;
use App\Services\SMPP\SmppTentativaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SmppEnviarRealCommand extends Command
{
    protected $signature = 'smpp:enviar-real
        {--sms-id= : Identificador da SMS}
        {--sender= : Sender ID autorizado pela Movitel}
        {--confirmar : Autorizar uma submissão real}';

    protected $description = 'Envia uma única SMS real através da Movitel';

    public function handle(
        SmppConnectionManager $manager,
        SmppDispatcher $dispatcher,
        SmppEnvioService $envioService,
        SmppTentativaService $tentativaService,
        SmppRecuperacaoService $recuperacaoService,
        SmppRateLimiter $rateLimiter
    ): int {

        $smsId = (int) $this->option('sms-id');
        $sender = trim((string) $this->option('sender'));

        if ($smsId !== 4 || !$this->option('confirmar')) {
            $this->error(
                'Nesta fase, apenas a SMS #4 pode ser enviada, mediante confirmação.'
            );

            return self::FAILURE;
        }

        if (!preg_match('/^[A-Za-z0-9]{1,11}$/', $sender)) {
            $this->error('Sender ID inválido.');

            return self::FAILURE;
        }

        $lock = Cache::store('redis')->lock('smpp:movitel:worker', 300);

        if (!$lock->get()) {
            $this->error(
                'Já existe uma sessão SMPP activa.'
            );

            return self::FAILURE;
        }

        $sessionId = null;
        $tentativaId = null;

        try {

            // 1. Validar a SMS e as reservas.
            $sms = $envioService->preparar($smsId);

            // 2. Confirmar que o Sender ID corresponde
            // ao registo associado à SMS.
            $senderRegisto = DB::table('sender_ids')
                ->where('id', $sms->sender_id)
                ->first();

            if (!$senderRegisto) {
                throw new RuntimeException(
                    'Sender ID não encontrado.'
                );
            }

           $senderGuardado = $senderRegisto->sender;

            if ($senderRegisto->estado !== 'APROVADO' || $senderRegisto->operadora !== 'MOVITEL' || !$senderRegisto->aprovado_operadora_em) {
                throw new RuntimeException(
                    'Sender ID não está aprovado para envio pela Movitel.'
                );
            }

            if (!is_string($senderGuardado) || $senderGuardado === '' || $senderGuardado !== $sender) {
                throw new RuntimeException(
                    'Sender ID não corresponde ao da SMS.'
                );
            }

            // deve ter sido confirmada previamente.

            // 3. Normalizar número de Moçambique.
            $telefone = preg_replace('/\D+/','',(string) $sms->telefone);

            if (preg_match('/^8[2-7][0-9]{7}$/', $telefone)) {
                $telefone = '258' . $telefone;
            }

            if (!preg_match('/^2588[2-7][0-9]{7}$/', $telefone)) {
                throw new RuntimeException(
                    'Número moçambicano inválido.'
                );
            }

            // 4. Estabelecer sessão SMPP.
            $connection = $manager->getConnection();

            $sessionId = $manager->sessionId();

            if (!$sessionId || !$connection->isBound()) {
                throw new RuntimeException(
                    'Sessão SMPP não autenticada.'
                );
            }

            $dispatcher->clearSessionState();

            $this->info(
                'Ligação SMPP estabelecida: ' .
                $manager->activeHost()
            );

            // 5. Preparar a PDU em memória.
            $sequence = $connection->prepareSubmit(
                $sender,
                $telefone,
                (string) $sms->mensagem
            );

            // 6. Persistir tentativa antes da escrita.
            $tentativaId = $envioService->prepararTentativa(
                smsId: $smsId,
                sessionId: $sessionId,
                sequenceNumber: $sequence,
                host: (string) $manager->activeHost(),
                porta: (int) config('services.smpp.port', 8014),
                workerId: $manager->workerId(),
                tentativaService: $tentativaService
            );

            $this->info(
                "Tentativa #{$tentativaId} preparada."
            );

            // 7. Aplicar limite global de TPS.
            if (!$rateLimiter->acquire()) {
                throw new RuntimeException(
                    'Limite global de TPS atingido. ' .
                    'Tentativa mantida como PREPARADA.'
                );
            }

            // 8. Persistir SUBMETENDO antes da escrita.
            $tentativaService->iniciarSubmissao(
                $tentativaId
            );

            // 9. Enviar exactamente uma PDU.
            $connection->sendPreparedSubmit($sequence);

            $this->info(
                'PDU transmitida. Aguardar resposta da Movitel.'
            );

            // 10. Processar resposta pelo dispatcher.
            $deadline = microtime(true) + 60;
            $respostaRecebida = false;

            while (microtime(true) < $deadline) {

                $dispatcher->processNext();

                $resposta = $dispatcher
                    ->takeSubmitResponse($sequence);

                if ($resposta !== null) {
                    $respostaRecebida = true;
                    break;
                }

                $dispatcher->heartbeatIfNeeded();
                $dispatcher->assertHeartbeatHealthy();
            }

            if (!$respostaRecebida) {
                throw new RuntimeException(
                    'Sem resposta definitiva da Movitel.'
                );
            }

            // O dispatcher já processou a confirmação
            // financeira dentro de SmppRespostaService.
            $smsActual = Sms::findOrFail($smsId);

            $this->info(
                'Estado final da SMS: ' .
                $smsActual->estado
            );

            return in_array(
                $smsActual->estado,
                ['ENVIADO', 'ENTREGUE'],
                true
            )
                ? self::SUCCESS
                : self::FAILURE;

        } catch (Throwable $e) {

            $this->error(
                'Erro no envio: ' . $e->getMessage()
            );

            report($e);

            return self::FAILURE;

        } finally {

            // Encerrar antes de recuperar tentativas.
            try {
                $manager->disconnect();
            } catch (Throwable $e) {
                report($e);
            }

            if ($sessionId) {
                try {
                    $recuperacaoService->recuperarSessao(
                        $sessionId
                    );
                } catch (Throwable $e) {
                    report($e);
                }
            }

            $dispatcher->clearSessionState();

            try {
                $lock->release();
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
