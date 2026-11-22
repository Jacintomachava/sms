<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OtpConfiguracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtpConfiguracaoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | IDENTIFICAR CONTA AUTENTICADA
    |--------------------------------------------------------------------------
    */
    private function contaId(): int
    {
        $contaId = (int) session('conta_id');

        abort_if(!auth()->check() || $contaId <= 0, 403);

        $autorizado = DB::table('conta_user')
            ->join('contas', 'contas.id', '=', 'conta_user.conta_id')
            ->where('conta_user.conta_id', $contaId)
            ->where('conta_user.user_id', auth()->id())
            ->where('conta_user.estado', 'ACTIVO')
            ->where('contas.estado', 'ACTIVA')
            ->exists();

        abort_unless($autorizado, 403);

        return $contaId;
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR / MOSTRAR CONFIGURAÇÃO OTP
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $contaId = $this->contaId();

        $configuracao = OtpConfiguracao::query()
            ->firstOrCreate(
                ['conta_id' => $contaId],
                [
                    'tamanho_codigo' => 6,
                    'validade_minutos' => 5,
                    'max_tentativas' => 5,
                    'intervalo_reenvio_segundos' => 60,
                    'activo' => true,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | SENDERS AUTORIZADOS PARA ESTA CONTA
        |--------------------------------------------------------------------------
        */
        $senders = DB::table('sender_ids')
            ->join(
                'conta_sender_ids',
                'conta_sender_ids.sender_id',
                '=',
                'sender_ids.id'
            )
            ->where('conta_sender_ids.conta_id', $contaId)
            ->where('conta_sender_ids.estado', 'ACTIVO')
            ->where('sender_ids.estado', 'APROVADO')
            ->select(
                'sender_ids.id',
                'sender_ids.sender',
                'sender_ids.tipo'
            )
            ->orderBy('sender_ids.sender')
            ->get();

        return view('cliente.otp.configuracao', compact(
            'configuracao',
            'senders'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR CONFIGURAÇÃO OTP
    |--------------------------------------------------------------------------
    */
    public function update(Request $request)
    {
        $contaId = $this->contaId();

        $dados = $request->validate([
            'sender_id' => [
                'required',
                'integer',
                Rule::exists('conta_sender_ids', 'sender_id')
                    ->where('conta_id', $contaId)
                    ->where('estado', 'ACTIVO'),
            ],

            'tamanho_codigo' => [
                'required',
                'integer',
                'between:4,8',
            ],

            'validade_minutos' => [
                'required',
                'integer',
                'between:1,30',
            ],

            'max_tentativas' => [
                'required',
                'integer',
                'between:1,10',
            ],

            'intervalo_reenvio_segundos' => [
                'required',
                'integer',
                'between:30,600',
            ],

            'activo' => [
                'required',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | GARANTIR QUE O SENDER ESTÁ APROVADO
        |--------------------------------------------------------------------------
        */
        $senderAprovado = DB::table('sender_ids')
            ->where('id', $dados['sender_id'])
            ->where('estado', 'APROVADO')
            ->exists();

        if (!$senderAprovado) {
            return back()
                ->withErrors([
                    'sender_id' => 'O Sender seleccionado não está aprovado.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | GUARDAR CONFIGURAÇÃO
        |--------------------------------------------------------------------------
        */
        OtpConfiguracao::updateOrCreate(
            ['conta_id' => $contaId],
            $dados
        );

        return redirect()
            ->route('configuracao')
            ->with('success', 'Configuração OTP actualizada com sucesso.');
    }
}
