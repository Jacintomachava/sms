<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsMonitorController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAGEM GLOBAL DE SMS
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'conta_id' => 'nullable|integer|exists:contas,id',
            'estado' => 'nullable|in:PENDENTE,PROCESSANDO,INCERTO,ENVIADO,ENTREGUE,FALHADO,REJEITADO,EXPIRADO',
            'telefone' => 'nullable|string|max:30',
            'inicio' => 'nullable|date',
            'fim' => 'nullable|date|after_or_equal:inicio',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CONSULTA GLOBAL
        |--------------------------------------------------------------------------
        */
        $query = DB::table('sms as s')
            ->leftJoin('contas as c', 'c.id', '=', 's.conta_id')
            ->select(
                's.id',
                's.conta_id',
                's.sender_id',
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

        /*
        |--------------------------------------------------------------------------
        | FILTRO POR CONTA
        |--------------------------------------------------------------------------
        */
        if (!empty($filtros['conta_id'])) {
            $query->where('s.conta_id', $filtros['conta_id']);
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO POR ESTADO
        |--------------------------------------------------------------------------
        */
        if (!empty($filtros['estado'])) {
            $query->where('s.estado', $filtros['estado']);
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO POR TELEFONE
        |--------------------------------------------------------------------------
        */
        if (!empty($filtros['telefone'])) {
            $query->where(
                's.telefone',
                'like',
                '%' . $filtros['telefone'] . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO POR DATA INICIAL
        |--------------------------------------------------------------------------
        */
        if (!empty($filtros['inicio'])) {
            $query->whereDate(
                's.created_at',
                '>=',
                $filtros['inicio']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO POR DATA FINAL
        |--------------------------------------------------------------------------
        */
        if (!empty($filtros['fim'])) {
            $query->whereDate(
                's.created_at',
                '<=',
                $filtros['fim']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINAÇÃO
        |--------------------------------------------------------------------------
        */
        $sms = $query
            ->orderByDesc('s.id')
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | CONTAS PARA FILTRO
        |--------------------------------------------------------------------------
        */
        $contas = DB::table('contas')
            ->select(
                'id',
                'nome',
                'nome_legal',
                'tipo_cobranca'
            )
            ->orderBy('nome')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ESTADOS DISPONÍVEIS
        |--------------------------------------------------------------------------
        */
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

    /*
    |--------------------------------------------------------------------------
    | DETALHES COMPLETOS DO SMS
    |--------------------------------------------------------------------------
    */
    public function show(int $id)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. SMS PRINCIPAL
        |--------------------------------------------------------------------------
        */
        $sms = DB::table('sms')
            ->where('id', $id)
            ->first();

        abort_if(!$sms, 404, 'SMS não encontrado.');

        /*
        |--------------------------------------------------------------------------
        | 2. CONTA RESPONSÁVEL
        |--------------------------------------------------------------------------
        */
        $conta = DB::table('contas')
            ->where('id', $sms->conta_id)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | 3. TENTATIVAS SMPP
        |--------------------------------------------------------------------------
        */
        $tentativas = DB::table('smpp_tentativas_envio')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. SEGMENTOS SMPP
        |--------------------------------------------------------------------------
        | Cada segmento pertence a uma tentativa SMPP.
        | Uma mensagem pode possuir várias tentativas.
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
                'seg.dlr_recebido_em',
                'seg.created_at',
                'seg.updated_at'
            )
            ->orderBy('seg.smpp_tentativa_envio_id')
            ->orderBy('seg.numero_segmento')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 5. DELIVERY REPORTS - DLR
        |--------------------------------------------------------------------------
        | Consulta os relatórios associados directamente ao SMS.
        |
        | Também procura relatórios sem sms_id quando existe um
        | provider_message_id associado aos segmentos.
        |--------------------------------------------------------------------------
        */

        $providerIds = $segmentos
            ->pluck('provider_message_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($sms->provider_message_id)) {
            $providerIds[] = $sms->provider_message_id;
        }

        $providerIds = array_values(array_unique($providerIds));

        $dlrs = DB::table('smpp_delivery_reports')
            ->where(function ($query) use ($id, $providerIds) {

                // DLR associados directamente ao SMS
                $query->where('sms_id', $id);

                // DLR sem sms_id, associados ao identificador Movitel
                if (!empty($providerIds)) {

                    $query->orWhere(function ($subquery) use ($providerIds) {

                        $subquery
                            ->whereNull('sms_id')
                            ->whereIn(
                                'provider_message_id',
                                $providerIds
                            );
                    });
                }
            })
            ->orderByDesc('recebido_em')
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 6. RESERVAS DE CRÉDITOS E STOCK
        |--------------------------------------------------------------------------
        */
        $reservas = DB::table('sms_reservas')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 7. CONSUMOS DE CRÉDITOS DO CLIENTE
        |--------------------------------------------------------------------------
        */
        $consumosCreditos = DB::table('sms_consumos_creditos')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 8. CONSUMOS DE STOCK MOVITEL
        |--------------------------------------------------------------------------
        */
        $consumosStock = DB::table('sms_consumos_stock')
            ->where('sms_id', $id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 9. RECONCILIAÇÕES
        |--------------------------------------------------------------------------
        */
        $reconciliacoes = DB::table('sms_reconciliacoes')
            ->where('sms_id', $id)
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 10. OUTBOX
        |--------------------------------------------------------------------------
        */
        $outbox = DB::table('sms_outbox')
            ->where('sms_id', $id)
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 11. RESUMO FINANCEIRO
        |--------------------------------------------------------------------------
        */

        // Valor comercial dos créditos pré-pagos consumidos
        $receitaPrePago = (float) $consumosCreditos
            ->sum('valor_total');

        // Custo real registado no stock Movitel
        $custoMovitel = (float) $consumosStock
            ->sum('custo_total');

        // Segmentos consumidos no stock Movitel
        $segmentosConsumidos = (int) $consumosStock
            ->sum('segmentos');

        /*
        |--------------------------------------------------------------------------
        | 12. RESUMO DOS SEGMENTOS
        |--------------------------------------------------------------------------
        */

        $totalSegmentos = $segmentos->count();

        $segmentosAceites = $segmentos->filter(
            fn ($segmento) =>
                $segmento->command_status !== null &&
                (int) $segmento->command_status === 0
        )->count();

        $segmentosEntregues = $segmentos->filter(
            fn ($segmento) =>
                in_array(
                    strtoupper($segmento->dlr_estado ?? ''),
                    ['DELIVRD', 'ENTREGUE']
                )
        )->count();

        $segmentosSemDlr = $segmentos->filter(
            fn ($segmento) =>
                empty($segmento->dlr_estado)
        )->count();

        $segmentosFalhados = $segmentos->filter(
            fn ($segmento) =>
                in_array(
                    strtoupper($segmento->dlr_estado ?? ''),
                    ['UNDELIV', 'REJECTD', 'EXPIRED']
                )
        )->count();

        /*
        |--------------------------------------------------------------------------
        | 13. RETORNAR VIEW
        |--------------------------------------------------------------------------
        */
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
            'segmentosConsumidos',
            'totalSegmentos',
            'segmentosAceites',
            'segmentosEntregues',
            'segmentosSemDlr',
            'segmentosFalhados'
        ));
    }
}