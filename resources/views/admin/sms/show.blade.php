@extends('layouts.app')

@section('conteudo')

@php

    /*
    |--------------------------------------------------------------------------
    | FUNÇÕES AUXILIARES
    |--------------------------------------------------------------------------
    */

    $moeda = fn ($valor) =>
        number_format((float) $valor, 4, ',', '.') . ' MZN';

    $numero = fn ($valor) =>
        number_format((int) $valor, 0, ',', '.');

    $formatarData = function ($data) {
        return $data
            ? \Carbon\Carbon::parse($data)->format('d/m/Y H:i:s')
            : '-';
    };

    $coresEstado = [
        'PENDENTE' => 'secondary',
        'PROCESSANDO' => 'info',
        'INCERTO' => 'warning',
        'ENVIADO' => 'primary',
        'ENTREGUE' => 'success',
        'FALHADO' => 'danger',
        'REJEITADO' => 'danger',
        'EXPIRADO' => 'dark',
    ];

    $corEstado = $coresEstado[$sms->estado] ?? 'secondary';

    /*
    |--------------------------------------------------------------------------
    | DADOS DA MENSAGEM
    |--------------------------------------------------------------------------
    */

    $nomeConta = $conta->nome_legal
        ?? $conta->nome
        ?? 'Conta #' . $sms->conta_id;

    $destinatario = $sms->telefone ?? '-';

    $conteudo = $sms->mensagem ?? '-';

    /*
    |--------------------------------------------------------------------------
    | DADOS FINANCEIROS
    |--------------------------------------------------------------------------
    */

    $receita = (float) $consumosCreditos->sum('valor_total');

    $custo = (float) $consumosStock->sum('custo_total');

    $margem = $receita - $custo;

    $segmentosCreditos = (int) $consumosCreditos->sum('segmentos');

    $segmentosStock = (int) $consumosStock->sum('segmentos');

    /*
    |--------------------------------------------------------------------------
    | INDICADORES SMPP
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
        fn ($segmento) => empty($segmento->dlr_estado)
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
    | CORES DOS DLR
    |--------------------------------------------------------------------------
    */

    $corDlr = function ($estado) {

        return match (strtoupper($estado ?? '')) {
            'DELIVRD', 'ENTREGUE' => 'success',
            'UNDELIV', 'REJECTD', 'EXPIRED' => 'danger',
            'ENROUTE', 'ACCEPTD' => 'info',
            default => 'secondary',
        };

    };

    /*
    |--------------------------------------------------------------------------
    | HISTÓRICOS
    |--------------------------------------------------------------------------
    */

    $historicos = [
        'Reservas de Créditos e Stock' => $reservas,
        'Consumos de Créditos' => $consumosCreditos,
        'Consumos de Stock' => $consumosStock,
        'Reconciliações' => $reconciliacoes,
        'Outbox' => $outbox,
    ];

@endphp

<div class="container-fluid py-4">

    {{-- ========================================================= --}}
    {{-- CABEÇALHO --}}
    {{-- ========================================================= --}}

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="fas fa-sms text-primary me-2"></i>

                Detalhes do SMS #{{ $sms->id }}

            </h3>

            <p class="text-muted mb-0">

                Monitorização completa da mensagem, entrega e consumo

            </p>

        </div>

        <div class="d-flex gap-2">

            <span class="badge bg-{{ $corEstado }} px-3 py-2 d-flex align-items-center">

                {{ $sms->estado }}

            </span>

            <a href="{{ route('admin.sms.index') }}"
               class="btn btn-outline-secondary">

                <i class="fas fa-arrow-left me-1"></i>

                Voltar

            </a>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- INDICADORES PRINCIPAIS --}}
    {{-- ========================================================= --}}

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        Estado da Mensagem

                    </small>

                    <h4 class="fw-bold mt-2">

                        <span class="badge bg-{{ $corEstado }}">

                            {{ $sms->estado }}

                        </span>

                    </h4>

                    <small class="text-muted">

                        Estado actual do SMS

                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        Tentativas SMPP

                    </small>

                    <h3 class="fw-bold mt-2 text-primary">

                        {{ $numero($tentativas->count()) }}

                    </h3>

                    <small class="text-muted">

                        Tentativas registadas

                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        Segmentos SMPP

                    </small>

                    <h3 class="fw-bold mt-2">

                        {{ $numero($totalSegmentos) }}

                    </h3>

                    <small class="text-muted">

                        Registos de segmentos

                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        DLR Recebidos

                    </small>

                    <h3 class="fw-bold mt-2 text-success">

                        {{ $numero($dlrs->count()) }}

                    </h3>

                    <small class="text-muted">

                        Relatórios registados

                    </small>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- INFORMAÇÕES PRINCIPAIS --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">

            <i class="fas fa-info-circle text-primary me-2"></i>

            Informações da Mensagem

        </div>

        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Conta

                    </small>

                    <strong>

                        {{ $nomeConta }}

                    </strong>

                    <div class="text-muted small">

                        ID: {{ $sms->conta_id }}

                    </div>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Tipo de Cobrança

                    </small>

                    <strong>

                        {{ $conta->tipo_cobranca ?? '-' }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Destinatário

                    </small>

                    <strong>

                        {{ $destinatario }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Origem

                    </small>

                    <strong>

                        {{ $sms->origem }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Finalidade

                    </small>

                    <strong>

                        {{ $sms->finalidade }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Custeado por

                    </small>

                    <strong>

                        {{ $sms->custeado_por }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Facturável ao Cliente

                    </small>

                    @if($sms->facturavel_cliente)

                        <span class="badge bg-success">

                            SIM

                        </span>

                    @else

                        <span class="badge bg-secondary">

                            NÃO

                        </span>

                    @endif

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Encoding

                    </small>

                    <strong>

                        {{ $sms->encoding }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Caracteres

                    </small>

                    <strong>

                        {{ $sms->caracteres }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Segmentos Previstos

                    </small>

                    <strong>

                        {{ $sms->segmentos }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Tentativas de Envio

                    </small>

                    <strong>

                        {{ $sms->tentativas_envio }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Próxima Tentativa

                    </small>

                    <strong>

                        {{ $formatarData($sms->proxima_tentativa_em) }}

                    </strong>

                </div>


                <div class="col-12">

                    <small class="text-muted d-block mb-2">

                        Conteúdo da Mensagem

                    </small>

                    <div class="bg-light border rounded p-3"
                         style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $conteudo }}</div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- INFORMAÇÕES SMPP --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">

            <i class="fas fa-server text-primary me-2"></i>

            Informações SMPP / Movitel

        </div>

        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Provider

                    </small>

                    <strong>

                        {{ $sms->provider ?? '-' }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Provider Message ID

                    </small>

                    <code style="overflow-wrap: anywhere;">

                        {{ $sms->provider_message_id ?? '-' }}

                    </code>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Host SMPP

                    </small>

                    <strong>

                        {{ $sms->smpp_host ?? '-' }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Sequence Number

                    </small>

                    <strong>

                        {{ $sms->smpp_sequence ?? '-' }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Código do Provider

                    </small>

                    <strong>

                        {{ $sms->provider_codigo ?? '-' }}

                    </strong>

                </div>


                <div class="col-md-4">

                    <small class="text-muted d-block">

                        Estado DLR

                    </small>

                    <span class="badge bg-{{ $corDlr($sms->dlr_estado) }}">

                        {{ $sms->dlr_estado ?? 'SEM DLR' }}

                    </span>

                </div>


                <div class="col-md-6">

                    <small class="text-muted d-block">

                        Descrição do Provider

                    </small>

                    <div>

                        {{ $sms->provider_descricao ?? '-' }}

                    </div>

                </div>


                <div class="col-md-6">

                    <small class="text-muted d-block">

                        Código de Erro DLR

                    </small>

                    <strong>

                        {{ $sms->dlr_codigo_erro ?? '-' }}

                    </strong>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- LINHA TEMPORAL --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">

            <i class="fas fa-clock text-primary me-2"></i>

            Linha Temporal da Mensagem

        </div>

        <div class="card-body">

            <div class="border-start border-3 ps-3">

                <div class="mb-4">

                    <strong class="text-primary">

                        Mensagem Criada

                    </strong>

                    <div class="text-muted">

                        {{ $formatarData($sms->created_at) }}

                    </div>

                </div>


                @if($sms->enviado_em)

                    <div class="mb-4">

                        <strong class="text-primary">

                            Mensagem Submetida

                        </strong>

                        <div class="text-muted">

                            {{ $formatarData($sms->enviado_em) }}

                        </div>

                    </div>

                @endif


                @if($sms->dlr_recebido_em)

                    <div class="mb-4">

                        <strong class="text-info">

                            DLR Recebido

                        </strong>

                        <div class="text-muted">

                            {{ $formatarData($sms->dlr_recebido_em) }}

                        </div>

                    </div>

                @endif


                @if($sms->entregue_em)

                    <div class="mb-4">

                        <strong class="text-success">

                            Mensagem Entregue

                        </strong>

                        <div class="text-muted">

                            {{ $formatarData($sms->entregue_em) }}

                        </div>

                    </div>

                @endif


                @if($sms->falhado_em)

                    <div class="mb-4">

                        <strong class="text-danger">

                            Mensagem Falhada

                        </strong>

                        <div class="text-muted">

                            {{ $formatarData($sms->falhado_em) }}

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- INDICADORES DOS SEGMENTOS --}}
    {{-- ========================================================= --}}

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        Segmentos Aceites

                    </small>

                    <h3 class="fw-bold text-primary mt-2">

                        {{ $numero($segmentosAceites) }}

                    </h3>

                    <small class="text-muted">

                        Command Status = 0

                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        Segmentos Entregues

                    </small>

                    <h3 class="fw-bold text-success mt-2">

                        {{ $numero($segmentosEntregues) }}

                    </h3>

                    <small class="text-muted">

                        DLR de entrega confirmado

                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        Segmentos Sem DLR

                    </small>

                    <h3 class="fw-bold text-warning mt-2">

                        {{ $numero($segmentosSemDlr) }}

                    </h3>

                    <small class="text-muted">

                        Aguardam confirmação

                    </small>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">

                        Segmentos com Falha DLR

                    </small>

                    <h3 class="fw-bold text-danger mt-2">

                        {{ $numero($segmentosFalhados) }}

                    </h3>

                    <small class="text-muted">

                        Falha ou expiração

                    </small>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- SEGMENTOS SMPP --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white d-flex justify-content-between align-items-center">

            <div>

                <strong>

                    <i class="fas fa-layer-group text-primary me-2"></i>

                    Segmentos SMPP

                </strong>

                <small class="text-muted d-block">

                    Histórico individual de submissão e entrega

                </small>

            </div>

            <span class="badge bg-primary">

                {{ $numero($segmentos->count()) }}

            </span>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Tentativa</th>
                            <th>Segmento</th>
                            <th>Provider Message ID</th>
                            <th>Session ID</th>
                            <th>Sequence</th>
                            <th>Estado</th>
                            <th>Command Status</th>
                            <th>DLR</th>
                            <th>Submetido</th>
                            <th>Respondido</th>
                            <th>DLR Recebido</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($segmentos as $segmento)

                            @php

                                $corSegmento = $coresEstado[$segmento->estado]
                                    ?? 'secondary';

                            @endphp

                            <tr>

                                <td>

                                    <span class="badge bg-dark">

                                        #{{ $segmento->smpp_tentativa_envio_id }}

                                    </span>

                                </td>


                                <td>

                                    <strong>

                                        {{ $segmento->numero_segmento }}
                                        /
                                        {{ $segmento->total_segmentos }}

                                    </strong>

                                </td>


                                <td>

                                    <code style="overflow-wrap: anywhere;">

                                        {{ $segmento->provider_message_id ?? '-' }}

                                    </code>

                                </td>


                                <td>

                                    <small>

                                        {{ $segmento->session_id ?? '-' }}

                                    </small>

                                </td>


                                <td>

                                    {{ $segmento->sequence_number ?? '-' }}

                                </td>


                                <td>

                                    <span class="badge bg-{{ $corSegmento }}">

                                        {{ $segmento->estado ?? '-' }}

                                    </span>

                                </td>


                                <td>

                                    @if($segmento->command_status === null)

                                        <span class="text-muted">-</span>

                                    @elseif((int) $segmento->command_status === 0)

                                        <span class="badge bg-success">

                                            0 - OK

                                        </span>

                                    @else

                                        <span class="badge bg-danger">

                                            {{ $segmento->command_status }}

                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if($segmento->dlr_estado)

                                        <span class="badge bg-{{ $corDlr($segmento->dlr_estado) }}">

                                            {{ $segmento->dlr_estado }}

                                        </span>

                                    @else

                                        <span class="badge bg-secondary">

                                            SEM DLR

                                        </span>

                                    @endif

                                </td>


                                <td>

                                    {{ $formatarData($segmento->submetido_em) }}

                                </td>


                                <td>

                                    {{ $formatarData($segmento->respondido_em) }}

                                </td>


                                <td>

                                    {{ $formatarData($segmento->dlr_recebido_em) }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="11"
                                    class="text-center text-muted py-4">

                                    Nenhum segmento SMPP registado.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- HISTÓRICO DE TENTATIVAS SMPP --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white d-flex justify-content-between align-items-center">

            <strong>

                <i class="fas fa-paper-plane text-primary me-2"></i>

                Tentativas SMPP

            </strong>

            <span class="badge bg-primary">

                {{ $tentativas->count() }}

            </span>

        </div>

        <div class="card-body">

            @if($tentativas->isEmpty())

                <p class="text-muted mb-0">

                    Nenhuma tentativa SMPP encontrada.

                </p>

            @else

                <div class="table-responsive">

                    <table class="table table-sm table-bordered align-middle">

                        <thead class="table-light">

                            <tr>

                                @foreach(array_keys((array) $tentativas->first()) as $campo)

                                    <th>

                                        {{ $campo }}

                                    </th>

                                @endforeach

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($tentativas as $tentativa)

                                <tr>

                                    @foreach((array) $tentativa as $valor)

                                        <td style="max-width: 300px; overflow-wrap: anywhere;">

                                            {{ $valor ?? '-' }}

                                        </td>

                                    @endforeach

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- HISTÓRICO DLR --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white d-flex justify-content-between align-items-center">

            <strong>

                <i class="fas fa-check-double text-primary me-2"></i>

                Delivery Reports (DLR)

            </strong>

            <span class="badge bg-primary">

                {{ $dlrs->count() }}

            </span>

        </div>

        <div class="card-body">

            @if($dlrs->isEmpty())

                <div class="alert alert-warning mb-0">

                    Nenhum relatório de entrega encontrado para esta mensagem.

                </div>

            @else

                <div class="table-responsive">

                    <table class="table table-hover table-bordered align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>ID</th>
                                <th>Provider Message ID</th>
                                <th>Estado</th>
                                <th>Erro</th>
                                <th>Host</th>
                                <th>Sequence</th>
                                <th>Recebido</th>
                                <th>Processado</th>
                                <th>RAW</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($dlrs as $dlr)

                                <tr>

                                    <td>

                                        #{{ $dlr->id }}

                                    </td>


                                    <td>

                                        <code>

                                            {{ $dlr->provider_message_id ?? '-' }}

                                        </code>

                                    </td>


                                    <td>

                                        <span class="badge bg-{{ $corDlr($dlr->estado) }}">

                                            {{ $dlr->estado }}

                                        </span>

                                    </td>


                                    <td>

                                        {{ $dlr->network_error_code ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $dlr->host ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $dlr->sequence_number ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $formatarData($dlr->recebido_em) }}

                                    </td>


                                    <td>

                                        {{ $formatarData($dlr->processado_em) }}

                                    </td>


                                    <td>

                                        <details>

                                            <summary class="text-primary" style="cursor:pointer;">

                                                Ver RAW

                                            </summary>

                                            <pre class="bg-light p-2 rounded mt-2"
                                                 style="max-width: 400px; white-space: pre-wrap; overflow-wrap: anywhere;">{{ $dlr->raw_receipt ?? '-' }}</pre>

                                        </details>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- RESUMO FINANCEIRO --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">

            <i class="fas fa-coins text-primary me-2"></i>

            Consumo de Créditos e Stock

        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted">

                            Segmentos Cobrados

                        </small>

                        <h4 class="fw-bold mt-2">

                            {{ $numero($segmentosCreditos) }}

                        </h4>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted">

                            Segmentos Consumidos Movitel

                        </small>

                        <h4 class="fw-bold mt-2">

                            {{ $numero($segmentosStock) }}

                        </h4>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted">

                            Valor dos Créditos Consumidos

                        </small>

                        <h4 class="fw-bold mt-2 text-success">

                            {{ $moeda($receita) }}

                        </h4>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 h-100">

                        <small class="text-muted">

                            Custo Movitel

                        </small>

                        <h4 class="fw-bold mt-2 text-danger">

                            {{ $moeda($custo) }}

                        </h4>

                    </div>

                </div>

            </div>


            <hr>


            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>

                    <strong>

                        Diferença entre valor consumido e custo

                    </strong>

                    <div class="text-muted small">

                        No POS_PAGO, a receita pode estar associada
                        ao ciclo de consumo e à factura.

                    </div>

                </div>

                <h4 class="fw-bold mb-0 {{ $margem >= 0 ? 'text-success' : 'text-danger' }}">

                    {{ $moeda($margem) }}

                </h4>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- HISTÓRICOS ADICIONAIS --}}
    {{-- ========================================================= --}}

    @foreach($historicos as $titulo => $registos)

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <strong>

                    {{ $titulo }}

                </strong>

                <span class="badge bg-secondary">

                    {{ $registos->count() }}

                </span>

            </div>

            <div class="card-body">

                @if($registos->isEmpty())

                    <p class="text-muted mb-0">

                        Nenhum registo encontrado.

                    </p>

                @else

                    <div class="table-responsive">

                        <table class="table table-sm table-bordered align-middle">

                            <thead class="table-light">

                                <tr>

                                    @foreach(array_keys((array) $registos->first()) as $campo)

                                        <th>

                                            {{ $campo }}

                                        </th>

                                    @endforeach

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($registos as $registo)

                                    <tr>

                                        @foreach((array) $registo as $valor)

                                            <td style="max-width: 350px; overflow-wrap: anywhere;">

                                                @if(is_array($valor) || is_object($valor))

                                                    <pre class="mb-0 small">{{ json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                                                @else

                                                    {{ $valor ?? '-' }}

                                                @endif

                                            </td>

                                        @endforeach

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @endif

            </div>

        </div>

    @endforeach


    {{-- ========================================================= --}}
    {{-- DLR RAW PRINCIPAL --}}
    {{-- ========================================================= --}}

    @if($sms->dlr_raw)

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white fw-bold">

                DLR RAW Principal

            </div>

            <div class="card-body">

                <pre class="bg-light p-3 rounded mb-0"
                     style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $sms->dlr_raw }}</pre>

            </div>

        </div>

    @endif

</div>

@endsection