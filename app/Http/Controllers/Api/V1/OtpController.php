<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | IDENTIFICAR CONTA PELA API KEY
    |--------------------------------------------------------------------------
    */
    private function contaId(Request $request): int
    {
        $contaId = (int) $request->attributes->get('conta_id');

        abort_if(
            $contaId <= 0,
            401,
            'API Key inválida ou conta não autenticada.'
        );

        return $contaId;
    }

    /*
    |--------------------------------------------------------------------------
    | ENVIAR OTP
    |--------------------------------------------------------------------------
    */
    public function send(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'telefone' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        $otp = $this->otpService->enviar(
            $this->contaId($request),
            $dados['telefone']
        );

        return response()->json([
            'status' => 1,
            'message' => 'Pedido de envio OTP registado com sucesso.',
            'data' => [
                'referencia' => $otp->referencia,
                'telefone' => $otp->telefone,
                'sms_id' => $otp->sms_id,
                'expira_em' => $otp->expira_em->toIso8601String(),
            ],
        ], 202);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR OTP
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'referencia' => [
                'required',
                'uuid',
            ],
            'codigo' => [
                'required',
                'string',
                'regex:/^[0-9]{4,8}$/',
            ],
        ]);

        $otp = $this->otpService->verificar(
            $this->contaId($request),
            $dados['referencia'],
            $dados['codigo']
        );

        /*
        |--------------------------------------------------------------------------
        | SUCESSO
        |--------------------------------------------------------------------------
        */
        if ($otp->estado === 'VERIFICADO') {
            return response()->json([
                'status' => 1,
                'message' => 'Código OTP validado com sucesso.',
                'data' => [
                    'referencia' => $otp->referencia,
                    'telefone' => $otp->telefone,
                    'verificado_em' => $otp->verificado_em
                        ?->toIso8601String(),
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FALHAS
        |--------------------------------------------------------------------------
        */
        $mensagens = [
            'PENDENTE' => 'Código OTP inválido.',
            'EXPIRADO' => 'Código OTP expirado.',
            'BLOQUEADO' => 'Número máximo de tentativas atingido.',
            'CANCELADO' => 'Este código OTP foi substituído por outro.',
            'VERIFICADO' => 'Código OTP já utilizado.',
        ];

        return response()->json([
            'status' => 0,
            'message' => $mensagens[$otp->estado]
                ?? 'Não foi possível validar o código OTP.',
            'error' => [
                'code' => 'OTP_VALIDATION_FAILED',
            ],
            'data' => [
                'tentativas_restantes' => max(
                    0,
                    $otp->max_tentativas - $otp->tentativas
                ),
            ],
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | REENVIAR OTP
    |--------------------------------------------------------------------------
    */
    public function resend(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'telefone' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        $otp = $this->otpService->reenviar(
            $this->contaId($request),
            $dados['telefone']
        );

        return response()->json([
            'status' => 1,
            'message' => 'Novo pedido OTP registado com sucesso.',
            'data' => [
                'referencia' => $otp->referencia,
                'telefone' => $otp->telefone,
                'sms_id' => $otp->sms_id,
                'expira_em' => $otp->expira_em->toIso8601String(),
            ],
        ], 202);
    }
}
