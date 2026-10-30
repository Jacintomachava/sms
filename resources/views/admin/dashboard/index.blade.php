@extends('layouts.app')

@section('conteudo')

@php
    $coresCobertura = [
        'SAUDAVEL' => 'success',
        'ATENCAO' => 'warning',
        'CRITICA' => 'danger',
        'DEFICE' => 'danger',
        'SEM_STOCK' => 'danger',
    ];

    $corCobertura = $coresCobertura[$estadoCobertura] ?? 'secondary';

    $percentagemBarra = min(100, max(0, $cobertura));

    $formatarNumero = fn ($valor) => number_format($valor, 0, ',', '.');

    $formatarMoeda = fn ($valor) => number_format($valor, 2, ',', '.') . ' MZN';
@endphp

<div class="container-fluid py-4">

    {{-- CABEÇALHO --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                Dashboard Administrativo
            </h3>

            <p class="text-muted mb-0">
                Monitorização de stock, cobertura, consumo e operações InfoSMS
            </p>
        </div>

        <div class="d-flex gap-2 align-items-center">

            <span class="badge bg-primary px-3 py-2">
                MOVITEL
            </span>

            <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                onclick="window.location.reload()"
            >
                Actualizar
            </button>

        </div>

    </div>

    {{-- ALERTAS --}}
    @if($estadoCobertura === 'CRITICA' || $estadoCobertura === 'DEFICE' || $estadoCobertura === 'SEM_STOCK')

        <div class="alert alert-danger">
            <strong>Atenção!</strong>
            A cobertura do stock está em estado
            {{ str_replace('_', ' ', $estadoCobertura) }}.
            Verifique a necessidade de adquirir mais SMS à Movitel.
        </div>

    @elseif($estadoCobertura === 'ATENCAO')

        <div class="alert alert-warning">
            <strong>Atenção:</strong>
            A cobertura está abaixo de 70%.
            Recomenda-se acompanhar o consumo e as reservas.
        </div>

    @endif

    @if($diferencaStock != 0)

        <div class="alert alert-danger">
            <strong>Divergência de stock:</strong>
            Foi identificada uma diferença de
            {{ $formatarNumero($diferencaStock) }}
            segmentos entre o saldo contabilizado e o esperado.
        </div>

    @endif

    @if($smsIncertasLibertadas > 0)

        <div class="alert alert-warning">
            <strong>SMS com submissão incerta:</strong>
            Existem {{ $formatarNumero($smsIncertasLibertadas) }}
            mensagens com tentativa SMPP incerta e reserva de stock libertada.
            Recomenda-se verificar estes casos antes de qualquer reenvio.
        </div>

    @endif

    {{-- INDICADORES PRINCIPAIS --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Stock físico Movitel
                    </small>

                    <h3 class="fw-bold mt-2">
                        {{ $formatarNumero($stockFisico) }}
                    </h3>

                    <small class="text-muted">
                        Segmentos ainda existentes
                    </small>

                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Stock comprometido
                    </small>

                    <h3 class="fw-bold mt-2 text-warning">
                        {{ $formatarNumero($stockComprometido) }}
                    </h3>

                    <small class="text-muted">
                        Créditos pré-pagos livres + reservas de stock
                    </small>

                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Stock livre
                    </small>

                    <h3 class="fw-bold mt-2 {{ $stockLivre < 0 ? 'text-danger' : 'text-success' }}">
                        {{ $formatarNumero($stockLivre) }}
                    </h3>

                    <small class="text-muted">
                        Disponível para novos compromissos
                    </small>

                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Cobertura
                    </small>

                    <h3 class="fw-bold mt-2">
                        {{ number_format($cobertura, 2, ',', '.') }}%
                    </h3>

                    <div class="progress mb-2" style="height: 8px;">
                        <div
                            class="progress-bar bg-{{ $corCobertura }}"
                            role="progressbar"
                            style="width: {{ $percentagemBarra }}%"
                            aria-valuenow="{{ $percentagemBarra }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>
                    </div>

                    <span class="badge bg-{{ $corCobertura }}">
                        {{ str_replace('_', ' ', $estadoCobertura) }}
                    </span>

                </div>
            </div>
        </div>

    </div>

    {{-- DETALHES DO STOCK --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Distribuição do Stock
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span>Stock comprado</span>
                        <strong>{{ $formatarNumero($stockComprado) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Stock disponível nos lotes</span>
                        <strong>{{ $formatarNumero($stockDisponivelLote) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Stock reservado</span>
                        <strong class="text-warning">
                            {{ $formatarNumero($stockReservado) }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Stock consumido</span>
                        <strong>{{ $formatarNumero($stockConsumido) }}</strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>Stock físico actual</strong>
                        <strong class="text-primary">
                            {{ $formatarNumero($stockFisico) }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Compromissos e Reservas
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span>Créditos PRE_PAGO livres</span>
                        <strong>{{ $formatarNumero($creditosPrePago) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Créditos PRE_PAGO reservados</span>
                        <strong>{{ $formatarNumero($creditosPrePagoReservados) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Reservas PRE_PAGO</span>
                        <strong>{{ $formatarNumero($reservasPrePago) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Reservas POS_PAGO</span>
                        <strong>{{ $formatarNumero($reservasPosPago) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Reservas STOCK</span>
                        <strong class="text-warning">
                            {{ $formatarNumero($reservasStock) }}
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>Total comprometido</strong>
                        <strong class="text-danger">
                            {{ $formatarNumero($stockComprometido) }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- CONSUMO E CUSTOS --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Consumo e Autonomia
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span>Consumo hoje</span>
                        <strong>{{ $formatarNumero($consumoHoje) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Consumo mensal</span>
                        <strong>{{ $formatarNumero($consumoMes) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Média diária</span>
                        <strong>{{ number_format($mediaDiaria, 2, ',', '.') }}</strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>Autonomia estimada</strong>

                        <strong class="text-primary">
                            {{ $autonomia !== null
                                ? number_format($autonomia, 1, ',', '.') . ' dias'
                                : 'Sem dados'
                            }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Custos Movitel
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span>Custo hoje</span>
                        <strong>{{ $formatarMoeda($custoConsumoHoje) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Custo mensal</span>
                        <strong>{{ $formatarMoeda($custoConsumoMes) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Custo acumulado</span>
                        <strong>{{ $formatarMoeda($custoConsumoTotal) }}</strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>Segmentos consumidos</strong>
                        <strong>{{ $formatarNumero($stockConsumido) }}</strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- RECONCILIAÇÃO --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Stock esperado
                    </small>

                    <h3 class="fw-bold mt-2">
                        {{ $formatarNumero($stockEsperado) }}
                    </h3>

                    <small class="text-muted">
                        Compras menos consumos
                    </small>

                </div>
            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Stock contabilizado
                    </small>

                    <h3 class="fw-bold mt-2">
                        {{ $formatarNumero($stockActual) }}
                    </h3>

                    <small class="text-muted">
                        Disponível + reservado
                    </small>

                </div>
            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Diferença de stock
                    </small>

                    <h3 class="fw-bold mt-2 {{ $diferencaStock == 0 ? 'text-success' : 'text-danger' }}">
                        {{ $formatarNumero($diferencaStock) }}
                    </h3>

                    <span class="badge bg-{{ $diferencaStock == 0 ? 'success' : 'danger' }}">
                        {{ $estadoReconciliacao }}
                    </span>

                </div>
            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">
                        Diferença de movimentos
                    </small>

                    <h3 class="fw-bold mt-2 {{ $diferencaMovimentos == 0 ? 'text-success' : 'text-danger' }}">
                        {{ $formatarNumero($diferencaMovimentos) }}
                    </h3>

                    <small class="text-muted">
                        Consumos sem movimento correspondente
                    </small>

                </div>
            </div>

        </div>

    </div>

    {{-- MONITORIZAÇÃO SMPP --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">
            Monitorização SMPP
        </div>

        <div class="card-body">

            <div class="row g-3 text-center">

                <div class="col-xl-2 col-md-4 col-6">
                    <small class="text-muted">Pendentes</small>
                    <h4 class="fw-bold">{{ $formatarNumero($smsPendentes) }}</h4>
                </div>

                <div class="col-xl-2 col-md-4 col-6">
                    <small class="text-muted">Processando</small>
                    <h4 class="fw-bold text-primary">
                        {{ $formatarNumero($smsProcessando) }}
                    </h4>
                </div>

                <div class="col-xl-2 col-md-4 col-6">
                    <small class="text-muted">Enviadas</small>
                    <h4 class="fw-bold">{{ $formatarNumero($smsEnviadas) }}</h4>
                </div>

                <div class="col-xl-2 col-md-4 col-6">
                    <small class="text-muted">Entregues</small>
                    <h4 class="fw-bold text-success">
                        {{ $formatarNumero($smsEntregues) }}
                    </h4>
                </div>

                <div class="col-xl-2 col-md-4 col-6">
                    <small class="text-muted">Falhadas</small>
                    <h4 class="fw-bold text-danger">
                        {{ $formatarNumero($smsFalhadas) }}
                    </h4>
                </div>

                <div class="col-xl-2 col-md-4 col-6">
                    <small class="text-muted">Sem DLR</small>
                    <h4 class="fw-bold text-warning">
                        {{ $formatarNumero($smsSemDlr) }}
                    </h4>
                </div>

            </div>

            <hr>

            <div class="row g-3">

                <div class="col-md-4">

                    <div class="p-3 bg-light rounded">

                        <small class="text-muted">
                            Total de SMS
                        </small>

                        <h4 class="fw-bold mb-0">
                            {{ $formatarNumero($totalSms) }}
                        </h4>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="p-3 bg-light rounded">

                        <small class="text-muted">
                            Tentativas SMPP incertas
                        </small>

                        <h4 class="fw-bold text-warning mb-0">
                            {{ $formatarNumero($tentativasIncertas) }}
                        </h4>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="p-3 bg-light rounded">

                        <small class="text-muted">
                            SMS incertas com stock libertado
                        </small>

                        <h4 class="fw-bold text-danger mb-0">
                            {{ $formatarNumero($smsIncertasLibertadas) }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- GRÁFICO --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">
            Consumo de SMS — Últimos 7 dias
        </div>

        <div class="card-body">

            <div style="height: 320px;">
                <canvas id="graficoConsumo"></canvas>
            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const elemento = document.getElementById('graficoConsumo');

    if (!elemento || typeof Chart === 'undefined') {
        return;
    }

    const consumo = @json($consumoDiario);

    const labels = consumo.map(item => item.dia);

    const valores = consumo.map(item => Number(item.total));

    new Chart(elemento, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Segmentos consumidos',
                data: valores,
                borderWidth: 1,
                borderRadius: 5,
                backgroundColor: 'rgba(13, 110, 253, 0.7)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

});
</script>

@endpush