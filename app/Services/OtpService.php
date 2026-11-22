<?php

namespace App\Services;

use App\Models\OtpCodigo;
use App\Models\OtpConfiguracao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OtpService
{
    public function __construct(
        private SmsCriacaoService $smsCriacaoService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR TELEFONE
    |--------------------------------------------------------------------------
    */
    private function normalizarTelefone(string $telefone): string
    {
        $telefone = trim($telefone);

        if (!preg_match(
            '/^(?:\+?258)?(82|83|84|85|86|87|88)[0-9]{7}$/',
            $telefone
        )) {
            throw ValidationException::withMessages([
                'telefone' => 'Número de telefone moçambicano inválido.',
            ]);
        }

        return substr(
            preg_replace('/\D/', '', $telefone),
            -9
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GERAR CÓDIGO NUMÉRICO SEGURO
    |--------------------------------------------------------------------------
    */
    private function gerarCodigo(int $tamanho): string
    {
        if ($tamanho < 4 || $tamanho > 8) {
            throw new RuntimeException(
                'O tamanho do código OTP deve estar entre 4 e 8 dígitos.'
            );
        }

        $codigo = '';

        for ($i = 0; $i < $tamanho; $i++) {
            $codigo .= (string) random_int(0, 9);
        }

        return $codigo;
    }

    /*
    |--------------------------------------------------------------------------
    | ENVIAR / REENVIAR OTP
    |--------------------------------------------------------------------------
    */
    public function enviar(
        int $contaId,
        string $telefone
    ): OtpCodigo {

        $telefone = $this->normalizarTelefone($telefone);

        return DB::transaction(function () use (
            $contaId,
            $telefone
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. BLOQUEAR CONTA PARA SERIALIZAR PEDIDOS
            |--------------------------------------------------------------------------
            |
            | Evita que dois pedidos simultâneos da mesma conta
            | ultrapassem o intervalo de reenvio.
            */
            $conta = DB::table('contas')
                ->where('id', $contaId)
                ->lockForUpdate()
                ->first();

            if (!$conta || $conta->estado !== 'ACTIVA') {
                throw ValidationException::withMessages([
                    'conta' => 'Conta não encontrada ou inactiva.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. CONFIGURAÇÃO OTP
            |--------------------------------------------------------------------------
            */
            $configuracao = OtpConfiguracao::query()
                ->where('conta_id', $contaId)
                ->where('activo', true)
                ->first();

            if (!$configuracao) {
                throw ValidationException::withMessages([
                    'otp' => 'O serviço OTP não está activo nesta conta.',
                ]);
            }

            if (!$configuracao->sender_id) {
                throw ValidationException::withMessages([
                    'sender_id' => 'Configure o Sender ID do serviço OTP.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. VALIDAR CONFIGURAÇÃO
            |--------------------------------------------------------------------------
            */
            if (
                $configuracao->tamanho_codigo < 4 ||
                $configuracao->tamanho_codigo > 8 ||
                $configuracao->validade_minutos < 1 ||
                $configuracao->max_tentativas < 1 ||
                $configuracao->intervalo_reenvio_segundos < 1
            ) {
                throw new RuntimeException(
                    'Configuração OTP inválida.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. CONTROLAR INTERVALO DE REENVIO
            |--------------------------------------------------------------------------
            |
            | Verifica o último pedido independentemente do estado.
            | Assim, não é possível contornar o intervalo após
            | um OTP expirado, bloqueado ou verificado.
            */
            $ultimoOtp = OtpCodigo::query()
                ->where('conta_id', $contaId)
                ->where('telefone', $telefone)
                ->orderByDesc('id')
                ->first();

            if ($ultimoOtp) {

                $proximoEnvio = $ultimoOtp->created_at
                    ->copy()
                    ->addSeconds(
                        $configuracao->intervalo_reenvio_segundos
                    );

                if (now()->lessThan($proximoEnvio)) {

                    $segundos = now()->diffInSeconds($proximoEnvio);

                    throw ValidationException::withMessages([
                        'telefone' => sprintf(
                            'Aguarde %d segundos antes de solicitar outro código.',
                            (int) ceil($segundos)
                        ),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 5. GERAR CÓDIGO
            |--------------------------------------------------------------------------
            */
            $codigo = $this->gerarCodigo(
                $configuracao->tamanho_codigo
            );

            /*
            |--------------------------------------------------------------------------
            | 6. CRIAR REGISTO OTP
            |--------------------------------------------------------------------------
            */
            $otp = OtpCodigo::create([
                'conta_id' => $contaId,
                'sms_id' => null,
                'referencia' => (string) Str::uuid(),
                'telefone' => $telefone,
                'codigo_hash' => Hash::make($codigo),
                'tentativas' => 0,
                'max_tentativas' => $configuracao->max_tentativas,
                'expira_em' => now()->addMinutes(
                    $configuracao->validade_minutos
                ),
                'estado' => 'PENDENTE',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. MENSAGEM PADRÃO INFOSMS
            |--------------------------------------------------------------------------
            */
            $mensagem = sprintf(
                'O seu código InfoSMS é %s',
                $codigo
            );

            /*
            |--------------------------------------------------------------------------
            | 8. CRIAR SMS E RESERVAR CRÉDITOS
            |--------------------------------------------------------------------------
            |
            | O SmsCriacaoService:
            | - valida o Sender;
            | - cria a SMS;
            | - reserva créditos;
            | - cria a SMS Outbox.
            |
            | Não envia directamente à operadora.
            */
            $sms = $this->smsCriacaoService->criar(
                contaId: $contaId,
                senderId: $configuracao->sender_id,
                telefone: $telefone,
                mensagem: $mensagem,
                userId: null,
                origem: 'API',
                finalidade: 'OTP'
            );

            /*
            |--------------------------------------------------------------------------
            | 9. ASSOCIAR SMS AO OTP
            |--------------------------------------------------------------------------
            */
            $otp->sms_id = $sms->id;
            $otp->save();

            /*
            |--------------------------------------------------------------------------
            | 10. CANCELAR CÓDIGOS ANTERIORES
            |--------------------------------------------------------------------------
            |
            | Só cancela os anteriores depois de criar
            | a nova SMS e reservar os créditos.
            */
            OtpCodigo::query()
                ->where('conta_id', $contaId)
                ->where('telefone', $telefone)
                ->where('estado', 'PENDENTE')
                ->where('id', '<>', $otp->id)
                ->update([
                    'estado' => 'CANCELADO',
                ]);

            return $otp;

        }, 3);
    }

    /*
    |--------------------------------------------------------------------------
    | REENVIAR OTP
    |--------------------------------------------------------------------------
    |
    | Gera novo código e nova referência.
    | A referência anterior deixa de ser válida.
    */
    public function reenviar(
        int $contaId,
        string $telefone
    ): OtpCodigo {
        return $this->enviar($contaId, $telefone);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR OTP
    |--------------------------------------------------------------------------
    */
    public function verificar(
        int $contaId,
        string $referencia,
        string $codigo
    ): OtpCodigo {

        return DB::transaction(function () use (
            $contaId,
            $referencia,
            $codigo
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. LOCALIZAR OTP DA CONTA
            |--------------------------------------------------------------------------
            */
            $otp = OtpCodigo::query()
                ->where('conta_id', $contaId)
                ->where('referencia', $referencia)
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                throw ValidationException::withMessages([
                    'referencia' => 'Referência OTP inválida.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. VALIDAR ESTADO
            |--------------------------------------------------------------------------
            */
            if ($otp->estado !== 'PENDENTE') {
                return $otp;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. VERIFICAR EXPIRAÇÃO
            |--------------------------------------------------------------------------
            */
            if (now()->greaterThanOrEqualTo($otp->expira_em)) {

                $otp->update([
                    'estado' => 'EXPIRADO',
                ]);

                return $otp;
            }

            /*
            |--------------------------------------------------------------------------
            | 4. VERIFICAR LIMITE DE TENTATIVAS
            |--------------------------------------------------------------------------
            */
            if ($otp->tentativas >= $otp->max_tentativas) {

                $otp->update([
                    'estado' => 'BLOQUEADO',
                ]);

                return $otp;
            }

            /*
            |--------------------------------------------------------------------------
            | 5. COMPARAR CÓDIGO
            |--------------------------------------------------------------------------
            */
            if (!Hash::check($codigo, $otp->codigo_hash)) {

                $tentativas = $otp->tentativas + 1;

                $otp->update([
                    'tentativas' => $tentativas,
                    'estado' => $tentativas >= $otp->max_tentativas
                        ? 'BLOQUEADO'
                        : 'PENDENTE',
                ]);

                return $otp;
            }

            /*
            |--------------------------------------------------------------------------
            | 6. CONFIRMAR OTP
            |--------------------------------------------------------------------------
            */
            $otp->update([
                'estado' => 'VERIFICADO',
                'verificado_em' => now(),
            ]);

            return $otp;

        }, 3);
    }
}
