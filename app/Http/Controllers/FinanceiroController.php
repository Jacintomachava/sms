<?php

namespace App\Http\Controllers;

use App\Models\Carteira;
use App\Models\CicloConsumoSms;
use App\Models\CompraSms;
use App\Models\Conta;
use App\Models\Sms;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceiroController extends Controller
{
    public function index(Request $request)
    {
        $contaId = (int) session('conta_id');

        /*
        |--------------------------------------------------------------------------
        | CONTA
        |--------------------------------------------------------------------------
        */
        $conta = Conta::query()->where('id', $contaId)->where('estado', 'ACTIVA')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | PERÍODO ACTUAL
        |--------------------------------------------------------------------------
        */
        $inicioMes = now()->copy()->startOfMonth();
        $fimMes = now()->copy()->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | INDICADORES COMUNS PRE / POS
        |--------------------------------------------------------------------------
        |
        | SMS enviados = quantidade de mensagens.
        | Segmentos = quantidade efectivamente consumida/cobrada.
        |
        */
        $querySmsMes = Sms::query()->where('conta_id', $contaId)->whereBetween('created_at', [ $inicioMes, $fimMes,]);

        $smsEnviadosMes = (clone $querySmsMes)->count();
        $segmentosEnviadosMes = (int) (clone $querySmsMes)->sum('segmentos');

        /*
        |--------------------------------------------------------------------------
        | PRE-PAGO
        |--------------------------------------------------------------------------
        */
        $dadosPrePago = null;

        if ($conta->tipo_cobranca === 'PRE_PAGO') {

            $saldoSms = (int) (
                Carteira::query()
                    ->where('conta_id', $contaId)
                    ->value('saldo_sms') ?? 0
            );

            /*
            |--------------------------------------------------------------------------
            | COMPRAS CREDITADAS NO MÊS
            |--------------------------------------------------------------------------
            */
            $comprasMes = CompraSms::query()
                ->where('conta_id', $contaId)
                ->where('estado', 'PAGA')
                ->whereNotNull('creditada_em')
                ->whereBetween('creditada_em', [
                    $inicioMes,
                    $fimMes,
                ])
                ->get();

            $dadosPrePago = [
                'sms_enviados_mes' => $smsEnviadosMes,
                'segmentos_enviados_mes' => $segmentosEnviadosMes,
                'sms_comprados_mes' => (int) $comprasMes->sum('quantidade_sms'),
                'valor_comprado_mes' => $comprasMes->sum('valor_total'),
                'saldo_sms' => $saldoSms,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | POS-PAGO
        |--------------------------------------------------------------------------
        */
        $dadosPosPago = null;

        if ($conta->tipo_cobranca === 'POS_PAGO') {

            $cicloActual = CicloConsumoSms::query()
                ->where('conta_id', $contaId)
                ->where('periodo_inicio', $inicioMes->toDateString())
                ->where('periodo_fim', $fimMes->toDateString())
                ->first();

            $dadosPosPago = [
                'sms_enviados_mes' => $smsEnviadosMes,
                'segmentos_enviados_mes' => $segmentosEnviadosMes,
                'preco_unitario' => $cicloActual?->preco_unitario ?? 0,
                'valor_estimado' => $cicloActual?->valor_estimado ?? 0,
                'ciclo' => $cicloActual,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | HISTÓRICO DOS ÚLTIMOS 12 MESES
        |--------------------------------------------------------------------------
        */
        $grafico = $this->gerarHistoricoMensal($conta, $contaId);

        return view('financeiro.index', compact('conta','dadosPrePago','dadosPosPago','grafico'));
    }


    /*
    |--------------------------------------------------------------------------
    | HISTÓRICO MENSAL
    |--------------------------------------------------------------------------
    */
    private function gerarHistoricoMensal(Conta $conta, int $contaId): array {

        $labels = [];

        $valores = [];

        $smsEnviados = [];

        /*
        |--------------------------------------------------------------------------
        | 12 MESES
        |--------------------------------------------------------------------------
        */
        for ($i = 11; $i >= 0; $i--) {

            $mes = now()->copy()->subMonths($i);
            $inicio = $mes->copy()->startOfMonth();
            $fim = $mes ->copy()->endOfMonth();

            /*
            |--------------------------------------------------------------------------
            | LABEL
            |--------------------------------------------------------------------------
            |
            | Exemplo:
            | Out/25
            | Nov/25
            | ...
            | Set/26
            |
            */
            $labels[] = ucfirst( $mes->locale('pt') ->translatedFormat('M/y'));

            /*
            |--------------------------------------------------------------------------
            | SMS ENVIADOS
            |--------------------------------------------------------------------------
            */
            $quantidadeSms = Sms::query()
                ->where('conta_id', $contaId)
                ->whereBetween('created_at', [$inicio, $fim])
                ->count();

            $smsEnviados[] = (int) $quantidadeSms;

            /*
            |--------------------------------------------------------------------------
            | PRE-PAGO
            |--------------------------------------------------------------------------
            |
            | Valor das compras efectivamente creditadas.
            |
            */
            if ($conta->tipo_cobranca === 'PRE_PAGO') {

                $valor = CompraSms::query()
                    ->where('conta_id', $contaId)
                    ->where('estado', 'PAGA')
                    ->whereNotNull('creditada_em')
                    ->whereBetween('creditada_em', [$inicio, $fim])
                    ->sum('valor_total');

                $valores[] = (float) $valor;
            }

            /*
            |--------------------------------------------------------------------------
            | POS-PAGO
            |--------------------------------------------------------------------------
            |
            | Valor acumulado do ciclo daquele mês.
            |
            */
            elseif ($conta->tipo_cobranca === 'POS_PAGO') {

                $valor = CicloConsumoSms::query()
                    ->where('conta_id', $contaId)
                    ->where('periodo_inicio', $inicio->toDateString())
                    ->where('periodo_fim', $fim->toDateString())
                    ->value('valor_estimado');

                $valores[] = (float) ($valor ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'valores' => $valores,
            'sms_enviados' => $smsEnviados,
            'titulo_valores' => $conta->tipo_cobranca === 'PRE_PAGO' ? 'Valor comprado por mês' : 'Valor consumido por mês',
        ];
    }
}