@extends('layouts.app')

@section('conteudo')

@php

    /*
    |--------------------------------------------------------------------------
    | FUNÇÕES AUXILIARES
    |--------------------------------------------------------------------------
    */

    $formatarData = function ($data) {

        return $data
            ? \Carbon\Carbon::parse($data)->format('d/m/Y')
            : '-';

    };

    $formatarHora = function ($data) {

        return $data
            ? \Carbon\Carbon::parse($data)->format('H:i:s')
            : '-';

    };

    $moeda = function ($valor) {

        return number_format((float) $valor, 4, ',', '.') . ' MZN';

    };

    $coresEstado = [
        'PENDENTE'    => 'secondary',
        'PROCESSANDO' => 'info',
        'INCERTO'     => 'warning',
        'ENVIADO'     => 'primary',
        'ENTREGUE'    => 'success',
        'FALHADO'     => 'danger',
        'REJEITADO'   => 'danger',
        'EXPIRADO'    => 'dark',
    ];

    $corEstado = $coresEstado[$sms->estado] ?? 'secondary';

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
    | INFORMAÇÕES DA CONTA
    |--------------------------------------------------------------------------
    */

    $nomeConta = $conta->nome_legal
        ?? $conta->nome
        ?? 'Conta #' . $sms->conta_id;

    /*
    |--------------------------------------------------------------------------
    | INDICADORES
    |--------------------------------------------------------------------------
    */

    $totalTentativas = $tentativas->count();

    $totalSegmentos = $segmentos->count();

    $totalDlrs = $dlrs->count();

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
    | FINANCEIRO
    |--------------------------------------------------------------------------
    */

    $valorCreditos = (float) $consumosCreditos->sum('valor_total');

    $custoStock = (float) $consumosStock->sum('custo_total');

    $segmentosStock = (int) $consumosStock->sum('segmentos');

    $diferenca = $valorCreditos - $custoStock;

    /*
    |--------------------------------------------------------------------------
    | HISTÓRICOS ADICIONAIS
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

<div class="container-fluid">

    {{-- ====================================================== --}}
    {{-- CABEÇALHO --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div>

                            <h4 class="card-title mb-1">

                                Detalhes do SMS #{{ $sms->id }}

                            </h4>

                            <p class="text-muted mb-0">

                                Monitorização completa da mensagem

                            </p>

                        </div>

                        <div class="d-flex gap-2 align-items-center">

                            <span class="badge bg-{{ $corEstado }}">
                                {{ $sms->estado }}
                            </span>

                            <a
                                href="{{ route('admin.sms.index') }}"
                                class="btn btn-secondary btn-sm"
                            >

                                <i class="fa fa-arrow-left"></i>
                                Voltar

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- INDICADORES PRINCIPAIS --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-xl-3 col-md-6">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        Tentativas SMPP
                    </h6>

                    <h3 class="text-primary">
                        {{ $totalTentativas }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        Segmentos SMPP
                    </h6>

                    <h3>
                        {{ $totalSegmentos }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        Segmentos Entregues
                    </h6>

                    <h3 class="text-success">
                        {{ $segmentosEntregues }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        DLR Recebidos
                    </h6>

                    <h3 class="text-info">
                        {{ $totalDlrs }}
                    </h3>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- INFORMAÇÕES PRINCIPAIS --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Informações da Mensagem
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <label class="text-muted">
                                Conta
                            </label>

                            <h6>
                                {{ $nomeConta }}
                            </h6>

                            <small class="text-muted">
                                ID: {{ $sms->conta_id }}
                            </small>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Tipo de Cobrança
                            </label>

                            <h6>
                                {{ $conta->tipo_cobranca ?? '-' }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Destinatário
                            </label>

                            <h6>
                                {{ $sms->telefone }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Origem
                            </label>

                            <h6>
                                {{ $sms->origem }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Finalidade
                            </label>

                            <h6>
                                {{ $sms->finalidade }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Custeado por
                            </label>

                            <h6>
                                {{ $sms->custeado_por }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Encoding
                            </label>

                            <h6>
                                {{ $sms->encoding }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Caracteres
                            </label>

                            <h6>
                                {{ $sms->caracteres }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Segmentos Previstos
                            </label>

                            <h6>
                                {{ $sms->segmentos }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Facturável ao Cliente
                            </label>

                            <h6>

                                @if($sms->facturavel_cliente)

                                    <span class="badge bg-success">
                                        SIM
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        NÃO
                                    </span>

                                @endif

                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Data de Criação
                            </label>

                            <h6>
                                {{ $formatarData($sms->created_at) }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Hora de Criação
                            </label>

                            <h6>
                                {{ $formatarHora($sms->created_at) }}
                            </h6>

                        </div>


                        <div class="col-12">

                            <label class="text-muted">
                                Mensagem
                            </label>

                            <div
                                class="border rounded p-3 bg-light"
                                style="white-space: pre-wrap; overflow-wrap: anywhere;"
                            >{{ $sms->mensagem }}</div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- INFORMAÇÕES SMPP --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Informações SMPP / Movitel
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <label class="text-muted">
                                Provider
                            </label>

                            <h6>
                                {{ $sms->provider ?? '-' }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Provider Message ID
                            </label>

                            <h6>
                                <code>
                                    {{ $sms->provider_message_id ?? '-' }}
                                </code>
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Host SMPP
                            </label>

                            <h6>
                                {{ $sms->smpp_host ?? '-' }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Sequence Number
                            </label>

                            <h6>
                                {{ $sms->smpp_sequence ?? '-' }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Código do Provider
                            </label>

                            <h6>
                                {{ $sms->provider_codigo ?? '-' }}
                            </h6>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Estado DLR
                            </label>

                            <h6>

                                <span class="badge bg-{{ $corDlr($sms->dlr_estado) }}">
                                    {{ $sms->dlr_estado ?? 'SEM DLR' }}
                                </span>

                            </h6>

                        </div>


                        <div class="col-md-6">

                            <label class="text-muted">
                                Descrição do Provider
                            </label>

                            <p>
                                {{ $sms->provider_descricao ?? '-' }}
                            </p>

                        </div>


                        <div class="col-md-6">

                            <label class="text-muted">
                                Código de Erro DLR
                            </label>

                            <p>
                                {{ $sms->dlr_codigo_erro ?? '-' }}
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- LINHA TEMPORAL --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Linha Temporal
                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-responsive custom-scrollbar">

                        <table class="table table-bordered">

                            <thead>

                                <tr>
                                    <th>Evento</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>
                                    <td>
                                        <span class="badge bg-primary">
                                            SMS CRIADO
                                        </span>
                                    </td>

                                    <td>
                                        {{ $formatarData($sms->created_at) }}
                                    </td>

                                    <td>
                                        {{ $formatarHora($sms->created_at) }}
                                    </td>
                                </tr>


                                @if($sms->enviado_em)

                                    <tr>
                                        <td>
                                            <span class="badge bg-info">
                                                SMS ENVIADO
                                            </span>
                                        </td>

                                        <td>
                                            {{ $formatarData($sms->enviado_em) }}
                                        </td>

                                        <td>
                                            {{ $formatarHora($sms->enviado_em) }}
                                        </td>
                                    </tr>

                                @endif


                                @if($sms->dlr_recebido_em)

                                    <tr>
                                        <td>
                                            <span class="badge bg-info">
                                                DLR RECEBIDO
                                            </span>
                                        </td>

                                        <td>
                                            {{ $formatarData($sms->dlr_recebido_em) }}
                                        </td>

                                        <td>
                                            {{ $formatarHora($sms->dlr_recebido_em) }}
                                        </td>
                                    </tr>

                                @endif


                                @if($sms->entregue_em)

                                    <tr>
                                        <td>
                                            <span class="badge bg-success">
                                                SMS ENTREGUE
                                            </span>
                                        </td>

                                        <td>
                                            {{ $formatarData($sms->entregue_em) }}
                                        </td>

                                        <td>
                                            {{ $formatarHora($sms->entregue_em) }}
                                        </td>
                                    </tr>

                                @endif


                                @if($sms->falhado_em)

                                    <tr>
                                        <td>
                                            <span class="badge bg-danger">
                                                SMS FALHADO
                                            </span>
                                        </td>

                                        <td>
                                            {{ $formatarData($sms->falhado_em) }}
                                        </td>

                                        <td>
                                            {{ $formatarHora($sms->falhado_em) }}
                                        </td>
                                    </tr>

                                @endif

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- RESUMO DOS SEGMENTOS --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        Segmentos Aceites
                    </h6>

                    <h3 class="text-primary">
                        {{ $segmentosAceites }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        Segmentos Entregues
                    </h6>

                    <h3 class="text-success">
                        {{ $segmentosEntregues }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        Sem DLR
                    </h6>

                    <h3 class="text-warning">
                        {{ $segmentosSemDlr }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card">

                <div class="card-body">

                    <h6 class="text-muted">
                        DLR Falhado
                    </h6>

                    <h3 class="text-danger">
                        {{ $segmentosFalhados }}
                    </h3>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- SEGMENTOS SMPP --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Segmentos SMPP
                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-responsive custom-scrollbar">

                        <table class="display" id="sms-segmentos-table">

                            <thead>

                                <tr>
                                    <th>Tentativa</th>
                                    <th>Segmento</th>
                                    <th>Provider ID</th>
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

                                @foreach($segmentos as $segmento)

                                    @php

                                        $corSegmento = $coresEstado[$segmento->estado]
                                            ?? 'secondary';

                                    @endphp

                                    <tr>

                                        <td>
                                            #{{ $segmento->smpp_tentativa_envio_id }}
                                        </td>


                                        <td>
                                            <strong>
                                                {{ $segmento->numero_segmento }}
                                                /
                                                {{ $segmento->total_segmentos }}
                                            </strong>
                                        </td>


                                        <td>
                                            <code>
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
                                                {{ $segmento->estado }}
                                            </span>
                                        </td>


                                        <td>

                                            @if($segmento->command_status === null)

                                                -

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

                                            <span class="badge bg-{{ $corDlr($segmento->dlr_estado) }}">

                                                {{ $segmento->dlr_estado ?? 'SEM DLR' }}

                                            </span>

                                        </td>


                                        <td>

                                            <div>
                                                {{ $formatarData($segmento->submetido_em) }}
                                            </div>

                                            <small class="text-muted">
                                                {{ $formatarHora($segmento->submetido_em) }}
                                            </small>

                                        </td>


                                        <td>

                                            <div>
                                                {{ $formatarData($segmento->respondido_em) }}
                                            </div>

                                            <small class="text-muted">
                                                {{ $formatarHora($segmento->respondido_em) }}
                                            </small>

                                        </td>


                                        <td>

                                            <div>
                                                {{ $formatarData($segmento->dlr_recebido_em) }}
                                            </div>

                                            <small class="text-muted">
                                                {{ $formatarHora($segmento->dlr_recebido_em) }}
                                            </small>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- TENTATIVAS SMPP --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Histórico de Tentativas SMPP
                    </h5>

                </div>

                <div class="card-body">

                    @if($tentativas->isEmpty())

                        <p class="text-muted">
                            Nenhuma tentativa SMPP encontrada.
                        </p>

                    @else

                        <div class="table-responsive custom-scrollbar">

                            <table class="table table-bordered">

                                <thead>

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

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- DELIVERY REPORTS --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Delivery Reports (DLR)
                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-responsive custom-scrollbar">

                        <table class="display" id="sms-dlr-table">

                            <thead>

                                <tr>
                                    <th>ID</th>
                                    <th>Provider Message ID</th>
                                    <th>Estado</th>
                                    <th>Erro</th>
                                    <th>Host</th>
                                    <th>Sequence</th>
                                    <th>Data</th>
                                    <th>Hora</th>
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
                                            {{ $formatarHora($dlr->recebido_em) }}
                                        </td>


                                        <td>

                                            <button
                                                type="button"
                                                class="btn btn-primary btn-xs"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalDlr{{ $dlr->id }}"
                                            >
                                                Ver RAW
                                            </button>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- MODAIS DLR RAW --}}
    {{-- ====================================================== --}}

    @foreach($dlrs as $dlr)

        <div
            class="modal fade"
            id="modalDlr{{ $dlr->id }}"
            tabindex="-1"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-lg">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            DLR RAW #{{ $dlr->id }}
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>

                    </div>

                    <div class="modal-body">

                        <pre
                            class="bg-light p-3 rounded"
                            style="white-space: pre-wrap; overflow-wrap: anywhere;"
                        >{{ $dlr->raw_receipt ?? 'Sem conteúdo RAW' }}</pre>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Fechar
                        </button>

                    </div>

                </div>

            </div>

        </div>

    @endforeach


    {{-- ====================================================== --}}
    {{-- RESUMO FINANCEIRO --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Consumo de Créditos e Stock
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-3">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Segmentos Consumidos
                                </small>

                                <h4>
                                    {{ $segmentosStock }}
                                </h4>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Valor dos Créditos Consumidos
                                </small>

                                <h4 class="text-success">
                                    {{ $moeda($valorCreditos) }}
                                </h4>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Custo Movitel
                                </small>

                                <h4 class="text-danger">
                                    {{ $moeda($custoStock) }}
                                </h4>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Diferença
                                </small>

                                <h4 class="{{ $diferenca >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $moeda($diferenca) }}
                                </h4>

                            </div>

                        </div>

                    </div>

                    <div class="alert alert-info mt-3 mb-0">

                        O valor dos créditos consumidos corresponde aos registos
                        de consumo pré-pago. Para contas pós-pagas, a receita
                        pode estar associada ao ciclo de consumo e à facturação.

                        A diferença apresentada não representa necessariamente
                        o lucro da mensagem.

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- HISTÓRICOS ADICIONAIS --}}
    {{-- ====================================================== --}}

    @foreach($historicos as $titulo => $registos)

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header">

                        <h5 class="mb-0">
                            {{ $titulo }}
                        </h5>

                    </div>

                    <div class="card-body">

                        @if($registos->isEmpty())

                            <p class="text-muted mb-0">
                                Nenhum registo encontrado.
                            </p>

                        @else

                            <div class="table-responsive custom-scrollbar">

                                <table class="table table-bordered">

                                    <thead>

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

                                                            <pre class="mb-0">{{ json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

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

            </div>

        </div>

    @endforeach


    {{-- ====================================================== --}}
    {{-- DLR RAW PRINCIPAL --}}
    {{-- ====================================================== --}}

    @if($sms->dlr_raw)

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header">

                        <h5 class="mb-0">
                            DLR RAW Principal
                        </h5>

                    </div>

                    <div class="card-body">

                        <pre
                            class="bg-light p-3 rounded"
                            style="white-space: pre-wrap; overflow-wrap: anywhere;"
                        >{{ $sms->dlr_raw }}</pre>

                    </div>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection