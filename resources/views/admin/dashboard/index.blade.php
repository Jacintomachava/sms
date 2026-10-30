@extends('layouts.app')

@section('conteudo')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Dashboard Administrativo</h3>
            <p class="text-muted mb-0">
                Monitorização de stock, cobertura e operações InfoSMS
            </p>
        </div>

        <span class="badge bg-primary">
            MOVITEL
        </span>
    </div>

    {{-- STOCK --}}
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <small class="text-muted">Stock físico</small>
                    <h3 class="fw-bold mt-2">
                        {{ number_format($stockFisico, 0, ',', '.') }}
                    </h3>
                    <small>SMS disponíveis na operadora</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <small class="text-muted">Stock comprometido</small>
                    <h3 class="fw-bold mt-2">
                        {{ number_format($stockComprometido, 0, ',', '.') }}
                    </h3>
                    <small>Créditos vendidos + reservas</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <small class="text-muted">Stock livre</small>
                    <h3 class="fw-bold mt-2">
                        {{ number_format($stockLivre, 0, ',', '.') }}
                    </h3>
                    <small>Disponível para novos compromissos</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">

                    <small class="text-muted">Cobertura</small>

                    <h3 class="fw-bold mt-2">
                        {{ number_format($cobertura, 2, ',', '.') }}%
                    </h3>

                    @php
                        $cores = [
                            'SAUDAVEL' => 'success',
                            'ATENCAO' => 'warning',
                            'CRITICA' => 'danger',
                            'DEFICE' => 'danger',
                            'SEM_STOCK' => 'danger',
                        ];
                    @endphp

                    <span class="badge bg-{{ $cores[$estadoCobertura] ?? 'secondary' }}">
                        {{ str_replace('_', ' ', $estadoCobertura) }}
                    </span>

                </div>
            </div>
        </div>

    </div>

    {{-- COMPROMISSOS --}}
    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">
                    Compromissos de Stock
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span>Créditos PRE_PAGO livres</span>
                        <strong>
                            {{ number_format($creditosPrePago, 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Reservas PRE_PAGO</span>
                        <strong>
                            {{ number_format($reservasPrePago, 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Reservas POS_PAGO</span>
                        <strong>
                            {{ number_format($reservasPosPago, 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Reservas de STOCK</span>
                        <strong>
                            {{ number_format($stockReservado, 0, ',', '.') }}
                        </strong>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">
                    Consumo e Autonomia
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span>Consumo hoje</span>
                        <strong>
                            {{ number_format($consumoHoje, 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Consumo mensal</span>
                        <strong>
                            {{ number_format($consumoMes, 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Média diária</span>
                        <strong>
                            {{ number_format($mediaDiaria, 1, ',', '.') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Autonomia estimada</span>
                        <strong>
                            {{ $autonomia !== null ? $autonomia . ' dias' : 'Sem dados' }}
                        </strong>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">

                <small class="text-muted">
                    Diferença de Stock
                </small>

                <h3 class="fw-bold mt-2
                    {{ $diferencaStock == 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($diferencaStock, 0, ',', '.') }}
                </h3>

                <span class="badge bg-{{ $diferencaStock == 0 ? 'success' : 'danger' }}">
                    {{ $estadoReconciliacao }}
                </span>

            </div>
        </div>
    </div>

    {{-- SMPP --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white fw-bold">
            Monitorização SMPP
        </div>

        <div class="card-body">

            <div class="row text-center g-3">

                <div class="col-md-2">
                    <small class="text-muted">Enviadas</small>
                    <h4>{{ $smsEnviadas }}</h4>
                </div>

                <div class="col-md-2">
                    <small class="text-muted">Entregues</small>
                    <h4 class="text-success">{{ $smsEntregues }}</h4>
                </div>

                <div class="col-md-2">
                    <small class="text-muted">Falhadas</small>
                    <h4 class="text-danger">{{ $smsFalhadas }}</h4>
                </div>

                <div class="col-md-3">
                    <small class="text-muted">Sem DLR</small>
                    <h4 class="text-warning">{{ $smsSemDlr }}</h4>
                </div>

                <div class="col-md-3">
                    <small class="text-muted">Tentativas incertas</small>
                    <h4 class="text-danger">{{ $tentativasIncertas }}</h4>
                </div>

            </div>

        </div>
    </div>

</div>

@endsection