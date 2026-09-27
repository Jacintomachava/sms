<?php

namespace App\Http\Controllers;

use App\Models\Carteira;
use App\Models\SenderId;
use App\Models\Sms;
use App\Services\SmsSegmentService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Throwable;

class SmsController extends Controller
{
    public function index()
    {
        $contaId = (int) session('conta_id');

        /*
         * Somente Senders:
         * - aprovados
         * - associados à conta
         * - associação activa
         */
        $senders = SenderId::query()
            ->where('estado', 'APROVADO')
            ->whereHas('contas', function ($query) use ($contaId) {
                $query
                    ->where('contas.id', $contaId)
                    ->where('conta_sender_ids.estado','ACTIVO');
            })
            ->orderBy('sender')
            ->get();

        $saldoSms = Carteira::where('conta_id', $contaId)->value('saldo_sms') ?? 0;

        /*
         * Últimos envios.
         */
        $sms = Sms::query()
            ->where('conta_id', $contaId)
            ->with('sender')
            ->latest()
            ->limit(100)
            ->get();

        return view('sms.index', compact('senders','saldoSms','sms'));
    }

    public function historico()
    {
        $contaId = (int) session('conta_id');

        /*
         * Somente Senders:
         * - aprovados
         * - associados à conta
         * - associação activa
         */
        $senders = SenderId::query()
            ->where('estado', 'APROVADO')
            ->whereHas('contas', function ($query) use ($contaId) {
                $query
                    ->where('contas.id', $contaId)
                    ->where('conta_sender_ids.estado','ACTIVO');
            })
            ->orderBy('sender')
            ->get();

        $saldoSms = Carteira::where('conta_id', $contaId)->value('saldo_sms') ?? 0;

        /*
         * Últimos envios.
         */
        $sms = Sms::query()
            ->where('conta_id', $contaId)
            ->with('sender')
            ->latest()
            ->limit(100)
            ->get();

        return view('sms.historico.index', compact('senders','saldoSms','sms'));
    }


    public function calcular(Request $request, SmsSegmentService $segmentService) {
        
        $request->validate([
            'mensagem' => ['required','string','max:5000'],
        ]);

        return response()->json([
            'status' => 1,
            'data' => $segmentService->calcular($request->mensagem),
        ]);
    }

    public function enviar(Request $request, SmsService $smsService) {
        
        $request->validate([
            'sender_id' => ['required','integer'],
            'telefone' => ['required','string','max:20'],
            'mensagem' => ['required','string','max:5000'],
        ]);

        $origem = 'PAINEL';

        try {

            $sms = $smsService->enviar((int) session('conta_id'), (int) $request->sender_id, $request->telefone, $request->mensagem, (int) auth()->id(), $origem);

            return response()->json([
                'status' => 1,
                'message' => 'SMS registado com sucesso.',
                'data' => [
                    'id' => $sms->id,
                    'segmentos' => $sms->segmentos,
                    'estado' => $sms->estado,
                ],
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}