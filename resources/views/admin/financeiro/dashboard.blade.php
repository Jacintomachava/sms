@extends('layouts.app')

@section('conteudo')

@php
    $moeda = fn ($valor) => number_format($valor, 2, ',', '.') . ' MZN';

    $numero = fn ($valor) => number_format($valor, 0, ',', '.');

    $coresEstado = [
        'PAGA' => 'success',
        'PENDENTE' => 'warning',
        'VENCIDA' => 'danger',
        'CANCELADA' => 'secondary',
    ];
@endphp

<div class="container-fluid py-4">

    {{-- CABEÇALHO --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                Dashboard Financeiro
            </h3>

            <p class="text-muted mb-0">
                Gestão de vendas, facturação, recebimentos e rentabilidade
            </p>
        </div>

        <span class="badge bg-success px-3 py-2">
            INFOSMS | FINANCEIRO
        </span>

    </div>

    {{-- FILTROS --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <form method="GET"
                  action="{{ route('admin.financeiro.dashboard') }}">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label">
                            Mês
                        </label>

                        <select name="mes" class="form-select">

                            @php
                                $meses = [
                                    1 => 'Janeiro',
                                    2 => 'Fevereiro',
                                    3 => 'Março',
                                    4 => 'Abril',
                                    5 => 'Maio',
                                    6 => 'Junho',
                                    7 => 'Julho',
                                    8 => 'Agosto',
                                    9 => 'Setembro',
                                    10 => 'Outubro',
                                    11 => 'Novembro',
                                    12 => 'Dezembro'
                                ];
                            @endphp

                            @foreach($meses as $numeroMes => $nomeMes)

                                <option
                                    value="{{ $numeroMes }}"
                                    {{ $mes == $numeroMes ? 'selected' : '' }}
                                >
                                    {{ $nomeMes }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Ano
                        </label>

                        <input
                            type="number"
                            name="ano"
                            class="form-control"
                            min="2020"
                            max="2100"
                            value="{{ $ano }}"
                        >

                    </div>

                    <div class="col-md-3">

                        <button type="submit"
                                class="btn btn-primary w-100">

                            Filtrar

                        </button>

                    </div>

                    <div class="col-md-2">

                        <a href="{{ route('admin.financeiro.dashboard') }}"
                           class="btn btn-outline-secondary w-100">

                            Limpar

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- RECEITAS E RECEBIMENTOS --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Vendas PRE_PAGO
                    </small>

                    <h3 class="fw-bold mt-2 text-primary">
                        {{ $moeda($vendasPrePago) }}
                    </h3>

                    <small class="text-muted">
                        {{ $numero($numeroCompras) }} compras pagas
                    </small>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Facturação POS_PAGO
                    </small>

                    <h3 class="fw-bold mt-2">
                        {{ $moeda($facturacaoPosPago) }}
                    </h3>

                    <small class="text-muted">
                        {{ $numero($numeroFacturas) }} facturas emitidas
                    </small>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Recebimentos do mês
                    </small>

                    <h3 class="fw-bold mt-2 text-success">
                        {{ $moeda($recebimentos) }}
                    </h3>

                    <small class="text-muted">
                        Pagamentos registados
                    </small>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Contas a receber
                    </small>

                    <h3 class="fw-bold mt-2 text-danger">
                        {{ $moeda($contasReceber) }}
                    </h3>

                    <small class="text-muted">
                        Saldo acumulado das facturas
                    </small>

                </div>

            </div>

        </div>

    </div>

    {{-- MARGEM E CUSTOS --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Receita de consumo PRE_PAGO
                    </small>

                    <h3 class="fw-bold mt-2">
                        {{ $moeda($receitaConsumoPrePago) }}
                    </h3>

                    <small class="text-muted">
                        Créditos efectivamente consumidos
                    </small>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Custo Movitel
                    </small>

                    <h3 class="fw-bold mt-2 text-danger">
                        {{ $moeda($custoMovitel) }}
                    </h3>

                    <small class="text-muted">
                        {{ $numero($segmentosConsumidos) }} segmentos
                    </small>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Margem bruta PRE_PAGO
                    </small>

                    <h3 class="fw-bold mt-2
                        {{ $margemBrutaPrePago >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $moeda($margemBrutaPrePago) }}
                    </h3>

                    <small class="text-muted">
                        Consumos com custo relacionado
                    </small>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <small class="text-muted">
                        Margem percentual PRE_PAGO
                    </small>

                    <h3 class="fw-bold mt-2">
                        {{ number_format($percentagemMargemPrePago, 2, ',', '.') }}%
                    </h3>

                    <small class="text-muted">
                        Sobre a receita de consumo
                    </small>

                </div>

            </div>

        </div>

    </div>

    {{-- DETALHES --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Resumo de Recebimentos
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">

                        <span>Recebimentos PRE_PAGO</span>

                        <strong>
                            {{ $moeda($recebimentosPrePago) }}
                        </strong>

                    </div>

                    <div class="d-flex justify-content-between mb-3">

                        <span>Recebimentos POS_PAGO</span>

                        <strong>
                            {{ $moeda($recebimentosPosPago) }}
                        </strong>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">

                        <strong>Total recebido</strong>

                        <strong class="text-success">
                            {{ $moeda($recebimentos) }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Créditos Pré-Pagos
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">

                        <span>Créditos vendidos no mês</span>

                        <strong>
                            {{ $numero($quantidadeVendida) }}
                        </strong>

                    </div>

                    <div class="d-flex justify-content-between mb-3">

                        <span>Créditos ainda não utilizados</span>

                        <strong>
                            {{ $numero($creditosNaoUtilizados) }}
                        </strong>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">

                        <strong>Valor nominal não utilizado</strong>

                        <strong class="text-primary">
                            {{ $moeda($valorCreditosNaoUtilizados) }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- FACTURAÇÃO E STOCK --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Situação da Facturação
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">

                        <span>Facturas pendentes</span>

                        <span class="badge bg-warning text-dark">
                            {{ $numero($facturasPendentes) }}
                        </span>

                    </div>

                    <div class="d-flex justify-content-between">

                        <span>Facturas vencidas</span>

                        <span class="badge bg-danger">
                            {{ $numero($facturasVencidas) }}
                        </span>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white fw-bold">
                    Investimento Movitel
                </div>

                <div class="card-body">

                    <small class="text-muted">
                        Valor acumulado das compras de stock
                    </small>

                    <h3 class="fw-bold mt-2 text-primary">
                        {{ $moeda($investimentoStock) }}
                    </h3>

                    <small class="text-muted">
                        Investimento em segmentos SMS
                    </small>

                </div>

            </div>

        </div>

    </div>

    {{-- GRÁFICO --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">
            Evolução Financeira — Últimos 6 Meses
        </div>

        <div class="card-body">

            <div style="height: 350px;">
                <canvas id="graficoFinanceiro"></canvas>
            </div>

        </div>

    </div>

    {{-- ÚLTIMAS FACTURAS --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white fw-bold">
            Últimas Facturas POS_PAGO
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>
                            <th>Número</th>
                            <th>Conta</th>
                            <th>Emissão</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Estado</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($ultimasFacturas as $factura)

                            <tr>

                                <td>
                                    {{ $factura->numero }}
                                </td>

                                <td>
                                    #{{ $factura->conta_id }}
                                </td>

                                <td>
                                    {{ \Carbon\Carbon::parse($factura->data_emissao)->format('d/m/Y') }}
                                </td>

                                <td>
                                    {{ \Carbon\Carbon::parse($factura->data_vencimento)->format('d/m/Y') }}
                                </td>

                                <td>
                                    {{ $moeda($factura->valor_total) }}
                                </td>

                                <td>

                                    <span class="badge bg-{{ $coresEstado[$factura->estado] ?? 'secondary' }}">
                                        {{ $factura->estado }}
                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Nenhuma factura encontrada.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection

@push('script')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const elemento = document.getElementById('graficoFinanceiro');

    if (!elemento || typeof Chart === 'undefined') {
        return;
    }

    const dados = @json($grafico);

    new Chart(elemento, {

        type: 'line',

        data: {

            labels: dados.map(item => item.mes),

            datasets: [

                {
                    label: 'Vendas PRE_PAGO',
                    data: dados.map(item => Number(item.vendas)),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    tension: 0.3
                },

                {
                    label: 'Facturação POS_PAGO',
                    data: dados.map(item => Number(item.facturacao)),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,0.1)',
                    tension: 0.3
                },

                {
                    label: 'Custos Movitel',
                    data: dados.map(item => Number(item.custos)),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    tension: 0.3
                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {
                    beginAtZero: true
                }

            }

        }

    });

});
</script>

@endpush