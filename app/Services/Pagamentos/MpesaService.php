<?php

namespace App\Services\Pagamentos;

use Karson\MpesaPhpSdk\Mpesa;
use Throwable;

class MpesaService
{
    private Mpesa $mpesa;

    public function __construct()
    {
        $this->mpesa = new Mpesa();

        $this->mpesa->setApiKey(config('services.mpesa.api_key'));
        $this->mpesa->setPublicKey(config('services.mpesa.public_key'));
        $this->mpesa->setServiceProviderCode(config('services.mpesa.service_provider_code'));
        $this->mpesa->setEnv(config('services.mpesa.env'));
    }

    public function c2b(string $invoiceId, string $telefone, string $valor, string $referenceId): array {

        $telefone = $this->normalizarTelefone($telefone);

        try {

            $result = $this->mpesa->c2b($invoiceId, $telefone, $valor, $referenceId);

            $response = $result->response ?? null;

            if (!$response) {

                return [
                    'sucesso' => false,
                    'codigo' => null,
                    'descricao' => 'Resposta inválida do M-Pesa.',
                    'raw' => $this->normalizarResposta($result),
                ];
            }

            $codigo = $response->output_ResponseCode ?? null;
            $descricao = $response->output_ResponseDesc ?? null;

            return [
                'sucesso' => $codigo === 'INS-0',
                'codigo' => $codigo,
                'descricao' => $descricao,
                'transacao_id' => $response->output_TransactionID ?? null,
                'raw' => $this->normalizarResposta($result),
            ];

        } catch (Throwable $e) {

            report($e);

            return [
                'sucesso' => false,
                'codigo' => null,
                'descricao' => 'Não foi possível comunicar com o M-Pesa.',
                'transacao_id' => null,
                'raw' => null,

                // importante para diagnóstico
                'erro_tecnico' => $e->getMessage(),
            ];
        }
    }


    public function mensagemCodigo( ?string $codigo, ?string $descricao = null): string {

        return match ($codigo) {

            'INS-1' => 'Ocorreu um erro interno no M-Pesa.',
            'INS-5' => 'A transacção foi cancelada pelo cliente.',
            'INS-6' => 'A transacção falhou.',
            'INS-9' => 'O tempo de espera da transacção foi esgotado.',
            'INS-15' => 'O valor da transacção é inválido.',
            'INS-25' => 'As credenciais de segurança são inválidas.',
            'INS-2006' => 'Saldo insuficiente na conta M-Pesa.',
            'INS-2001' => 'PIN M-Pesa incorrecto.',
            default => $descricao ?: 'Não foi possível concluir o pagamento.',

        };
    }


    private function normalizarTelefone(string $telefone): string {

        $telefone = preg_replace('/\D+/','',$telefone);

        if (strlen($telefone) === 12 && str_starts_with($telefone, '258')) {

            return $telefone;
        }

        return '258' . $telefone;
    }

    private function normalizarResposta( mixed $result): ?array {

        return json_decode( json_encode($result), true);
    }
}