<?php

namespace App\Services\Pagamentos;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Karson\MpesaPhpSdk\Mpesa;
use Throwable;

class MpesaService
{
    private Mpesa $mpesa;

    public function __construct()
    {
        $this->mpesa = new Mpesa();

        $this->mpesa->setApiKey(
            config('services.mpesa.api_key')
        );

        $this->mpesa->setPublicKey(
            config('services.mpesa.public_key')
        );

        $this->mpesa->setServiceProviderCode(
            config('services.mpesa.service_provider_code')
        );

        $this->mpesa->setEnv(
            config('services.mpesa.env')
        );
    }

    /**
     * Iniciar pagamento C2B via M-Pesa.
     *
     * Não confirma compras nem credita SMS.
     * Apenas devolve a resposta do operador.
     */
    public function c2b(
        string $invoiceId,
        string $telefone,
        string $valor,
        string $referenceId
    ): array {

        $telefone = $this->normalizarTelefone($telefone);

        try {

            $result = $this->mpesa->c2b(
                $invoiceId,
                $telefone,
                $valor,
                $referenceId
            );

            $response = $result->response ?? null;

            /*
            |--------------------------------------------------------------------------
            | RESPOSTA VAZIA
            |--------------------------------------------------------------------------
            */
            if (!$response) {

                Log::warning('MPESA_C2B_RESPOSTA_VAZIA', [
                    'invoice_id' => $invoiceId,
                    'reference_id' => $referenceId,
                ]);

                return [
                    'sucesso' => false,
                    'resultado_confirmado' => false,
                    'codigo' => null,
                    'descricao' =>
                        'O M-Pesa não devolveu uma resposta válida.',
                    'transacao_id' => null,
                    'raw' => $this->normalizarResposta($result),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | INTERPRETAR RESPOSTA
            |--------------------------------------------------------------------------
            */
            $codigo = isset($response->output_ResponseCode)
                ? (string) $response->output_ResponseCode
                : null;

            $descricao = isset($response->output_ResponseDesc)
                ? (string) $response->output_ResponseDesc
                : null;

            $transacaoId = isset($response->output_TransactionID)
                ? (string) $response->output_TransactionID
                : null;

            $sucesso = $codigo === 'INS-0';

            /*
             * Por segurança, apenas INS-0 é considerado
             * uma confirmação definitiva nesta versão.
             *
             * Outros códigos exigem validação da
             * semântica oficial antes de desbloquear
             * novas cobranças.
             */
            $resultadoConfirmado = $sucesso;

            Log::info('MPESA_C2B_RESPOSTA', [
                'invoice_id' => $invoiceId,
                'reference_id' => $referenceId,
                'codigo' => $codigo,
                'resultado_confirmado' => $resultadoConfirmado,
                'transacao_id' => $transacaoId,
            ]);

            return [
                'sucesso' => $sucesso,
                'resultado_confirmado' => $resultadoConfirmado,
                'codigo' => $codigo,
                'descricao' => $descricao,
                'transacao_id' => $transacaoId,
                'raw' => $this->normalizarResposta($result),
            ];

        } catch (Throwable $e) {

            report($e);

            Log::warning('MPESA_C2B_RESULTADO_INCERTO', [
                'invoice_id' => $invoiceId,
                'reference_id' => $referenceId,
                'erro' => get_class($e),
            ]);

            /*
             * Uma excepção não significa que o
             * cliente não foi debitado.
             */
            return [
                'sucesso' => false,
                'resultado_confirmado' => false,
                'codigo' => null,
                'descricao' =>
                    'Não foi possível confirmar o resultado do M-Pesa.',
                'transacao_id' => null,
                'raw' => null,
                'erro_tecnico' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mensagens amigáveis para o cliente.
     */
    public function mensagemCodigo(
        ?string $codigo,
        ?string $descricao = null
    ): string {

        return match ($codigo) {

            'INS-0' =>
                'Pagamento confirmado com sucesso.',

            'INS-1' =>
                'Ocorreu um erro interno no M-Pesa.',

            'INS-5' =>
                'O M-Pesa informou que a transacção foi cancelada.',

            'INS-6' =>
                'O M-Pesa informou que a transacção falhou.',

            'INS-9' =>
                'O tempo de espera da transacção foi esgotado.',

            'INS-15' =>
                'O valor da transacção é inválido.',

            'INS-25' =>
                'As credenciais de segurança são inválidas.',

            'INS-2006' =>
                'O M-Pesa informou saldo insuficiente.',

            'INS-2001' =>
                'O M-Pesa informou PIN incorrecto.',

            default =>
                $descricao
                ?: 'Não foi possível confirmar o pagamento.',
        };
    }

    /**
     * Normalizar telefone para 25884XXXXXXX
     * ou 25885XXXXXXX.
     */
    private function normalizarTelefone(
        string $telefone
    ): string {

        $telefone = preg_replace(
            '/\D+/',
            '',
            $telefone
        );

        if (
            strlen($telefone) === 12 &&
            str_starts_with($telefone, '258')
        ) {
            $telefone = substr($telefone, 3);
        }

        if (!preg_match('/^(84|85)[0-9]{7}$/', $telefone)) {

            throw ValidationException::withMessages([
                'telefone' =>
                    'Número M-Pesa inválido. Utilize 84XXXXXXX ou 85XXXXXXX.',
            ]);
        }

        return '258' . $telefone;
    }

    /**
     * Converter a resposta do SDK para array.
     */
    private function normalizarResposta(
        mixed $result
    ): ?array {

        if ($result === null) {
            return null;
        }

        $json = json_encode($result);

        if ($json === false) {
            return null;
        }

        $dados = json_decode($json, true);

        return is_array($dados) ? $dados : null;
    }
}
