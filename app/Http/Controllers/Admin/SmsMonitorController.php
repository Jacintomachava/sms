<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsMonitorController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'conta_id' => 'nullable|integer|min:1',
            'estado' => 'nullable|string|max:50',
            'telefone' => 'nullable|string|max:30',
            'inicio' => 'nullable|date',
            'fim' => 'nullable|date|after_or_equal:inicio',
        ]);

        /*
        |--------------------------------------------------------------------------
        | LISTAGEM GLOBAL
        |--------------------------------------------------------------------------
        */
        $query = DB::table('sms as s')
            ->leftJoin('contas as c', 'c.id', '=', 's.conta_id')
            ->select(
                's.id',
                's.conta_id',
                's.telefone',
                's.mensagem',
                's.segmentos',
                's.encoding',
                's.finalidade',
                's.custeado_por',
                's.facturavel_cliente',
                's.origem',
                's.estado',
                's.provider',
                's.provider_message_id',
                's.dlr_estado',
                's.dlr_codigo_erro',
                's.enviado_em',
                's.entregue_em',
                's.created_at',
                'c.nome as conta_nome',
                'c.nome_legal as conta_nome_legal',
                'c.tipo_cobranca'
            );

        if (!empty($filtros['conta_id'])) {
            $query->where('s.conta_id', $filtros['conta_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('s.estado', $filtros['estado']);
        }

        if (!empty($filtros['telefone'])) {
            $query->where(
                's.telefone',
                'like',
                '%' . $filtros['telefone'] . '%'
            );
        }

        if (!empty($filtros['inicio'])) {
            $query->whereDate('s.created_at', '>=', $filtros['inicio']);
        }

        if (!empty($filtros['fim'])) {
            $query->whereDate('s.created_at', '<=', $filtros['fim']);
        }

        $sms = $query
            ->orderByDesc('s.id')
            ->paginate(25)
            ->withQueryString();

        $contas = DB::table('contas')
            ->orderBy('nome')
            ->get();

        $estados = [
            'PENDENTE',
            'PROCESSANDO',
            'INCERTO',
            'ENVIADO',
            'ENTREGUE',
            'FALHADO',
            'REJEITADO',
            'EXPIRADO',
        ];

        return view('admin.sms.index', compact(
            'sms',
            'contas',
            'estados'
        ));
    }

    public function show(int $id)
    {
        /*
        |--------------------------------------------------------------------------
        | SMS PRINCIPAL
        |--------------------------------------------------------------------------
        */
        $sms = DB::table('sms')
            ->where('id', $id)
            ->first();

        abort_if(!$sms, 404);

        /*
        |--------------------------------------------------------------------------
        | CONTA
        |--------------------------------------------------------------------------
        */
        $conta = DB::table('contas')
            ->where('id', $sms->conta_id)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | TENTATIVAS SMPP
        |--------------------------------------------------------------------------
        */
        $tentativas = DB::table('smpp_tentativas_envio')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | SEGMENTOS SMPP
        |--------------------------------------------------------------------------
        |
        | Confirmar a chave estrangeira da tabela
        | smpp_segmentos_envio antes de activar esta consulta.
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | SEGMENTOS SMPP
        |--------------------------------------------------------------------------
        | Cada segmento pertence a uma tentativa SMPP.
        | Uma mensagem pode ter várias tentativas.
        |--------------------------------------------------------------------------
        */
        $segmentos = DB::table('smpp_segmentos_envio as seg')
            ->join(
                'smpp_tentativas_envio as tentativa',
                'tentativa.id',
                '=',
                'seg.smpp_tentativa_envio_id'
            )
            ->where('tentativa.sms_id', $id)
            ->select(
                'seg.id',
                'seg.smpp_tentativa_envio_id',
                'seg.numero_segmento',
                'seg.total_segmentos',
                'seg.udh_referencia',
                'seg.session_id',
                'seg.sequence_number',
                'seg.provider_message_id',
                'seg.estado',
                'seg.command_status',
                'seg.dlr_estado',
                'seg.submetido_em',
                'seg.respondido_em',
                'seg.dlr_recebido_em'
            )
            ->orderBy('seg.smpp_tentativa_envio_id')
            ->orderBy('seg.numero_segmento')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | DELIVERY REPORTS
        |--------------------------------------------------------------------------
        |
        | Primeiro procuramos por sms_id.
        |
        | O provider_message_id é utilizado como alternativa
        | apenas quando o relatório não tem sms_id associado.
        |--------------------------------------------------------------------------
        */
        /*

        
        /*
        |--------------------------------------------------------------------------
        | RESERVAS
        |--------------------------------------------------------------------------
        */
        $reservas = DB::table('sms_reservas')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | CONSUMOS DE CRÉDITOS
        |--------------------------------------------------------------------------
        */
        $consumosCreditos = DB::table('sms_consumos_creditos')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | CONSUMOS DE STOCK
        |--------------------------------------------------------------------------
        */
        $consumosStock = DB::table('sms_consumos_stock')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RECONCILIAÇÕES
        |--------------------------------------------------------------------------
        */
        $reconciliacoes = DB::table('sms_reconciliacoes')
            ->where('sms_id', $id)
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | OUTBOX
        |--------------------------------------------------------------------------
        */
        $outbox = DB::table('sms_outbox')
            ->where('sms_id', $id)
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RESUMO FINANCEIRO
        |--------------------------------------------------------------------------
        */
        $receitaPrePago = (float) $consumosCreditos
            ->sum('valor_total');

        $custoMovitel = (float) $consumosStock
            ->sum('custo_total');

        $segmentosConsumidos = (int) $consumosStock
            ->sum('segmentos');

        return view('admin.sms.show', compact(
            'sms',
            'conta',
            'tentativas',
            'segmentos',
            'dlrs',
            'reservas',
            'consumosCreditos',
            'consumosStock',
            'reconciliacoes',
            'outbox',
            'receitaPrePago',
            'custoMovitel',
            'segmentosConsumidos'
        ));
    }
}