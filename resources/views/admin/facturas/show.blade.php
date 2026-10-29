@extends('layouts.app')

@section('conteudo')

<div class="container-fluid py-4">

    {{-- CABEÇALHO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Detalhes da Factura</h4>
            <p class="text-muted mb-0">
                {{ $factura->numero }}
            </p>
        </div>

        <a href="{{ route('admin.facturas.pdf', $factura->id) }}"
            class="btn btn-danger">
            <i class="fas fa-file-pdf"></i>
            Descarregar PDF
        </a>

        <a href="{{ route('admin.facturas.index') }}"
           class="btn btn-outline-secondary">
            Voltar às Facturas
        </a>

    </div>

    @php
        $totalPago = (float) $factura->pagamentos
            ->sum('pivot.valor_aplicado');

        $saldo = max(
            0,
            (float) $factura->valor_total - $totalPago
        );

        $percentagem = (float) $factura->valor_total > 0
            ? min(100, ($totalPago / (float) $factura->valor_total) * 100)
            : 0;
    @endphp

    {{-- INDICADORES --}}
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <span class="text-muted">Valor da Factura</span>
                    <h4 class="mt-2 mb-0">
                        {{ number_format($factura->valor_total, 2, ',', '.') }}
                        MZN
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <span class="text-muted">Total Pago</span>
                    <h4 class="mt-2 mb-0 text-success">
                        {{ number_format($totalPago, 2, ',', '.') }}
                        MZN
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <span class="text-muted">Saldo Pendente</span>
                    <h4 class="mt-2 mb-0 text-danger">
                        {{ number_format($saldo, 2, ',', '.') }}
                        MZN
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-4">

        {{-- DADOS DA FACTURA --}}
        <div class="col-lg-7">

            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informações da Factura</h5>
                </div>

                <div class="card-body">

                    <table class="table table-borderless">

                        <tr>
                            <th>Número</th>
                            <td>{{ $factura->numero }}</td>
                        </tr>

                        <tr>
                            <th>Conta</th>
                            <td>{{ $factura->conta->nome ?? '-' }}</td>
                        </tr>

                        <tr>
                            <th>Período</th>
                            <td>
                                {{ $factura->periodo_inicio?->format('d/m/Y') }}
                                até
                                {{ $factura->periodo_fim?->format('d/m/Y') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Data de emissão</th>
                            <td>
                                {{ $factura->data_emissao?->format('d/m/Y') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Data de vencimento</th>
                            <td>
                                {{ $factura->data_vencimento?->format('d/m/Y') ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>Estado</th>
                            <td>
                                @if($factura->estado === 'PAGA')
                                    <span class="badge bg-success">PAGA</span>
                                @elseif($factura->estado === 'VENCIDA')
                                    <span class="badge bg-danger">VENCIDA</span>
                                @elseif($factura->estado === 'PENDENTE')
                                    <span class="badge bg-warning text-dark">PENDENTE</span>
                                @else
                                    <span class="badge bg-secondary">
                                        {{ $factura->estado }}
                                    </span>
                                @endif
                            </td>
                        </tr>

                    </table>

                </div>
            </div>

        </div>

        {{-- CONSUMO --}}
        <div class="col-lg-5">

            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Resumo do Consumo</h5>
                </div>

                <div class="card-body">

                    <table class="table table-borderless">

                        <tr>
                            <th>SMS</th>
                            <td class="text-end">
                                {{ number_format($factura->quantidade_sms, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Segmentos</th>
                            <td class="text-end">
                                {{ number_format($factura->segmentos, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Preço por segmento</th>
                            <td class="text-end">
                                {{ number_format($factura->preco_unitario, 4, ',', '.') }}
                                MZN
                            </td>
                        </tr>

                        <tr>
                            <th>Subtotal</th>
                            <td class="text-end">
                                {{ number_format($factura->subtotal, 2, ',', '.') }}
                                MZN
                            </td>
                        </tr>

                        <tr class="border-top">
                            <th>Valor Total</th>
                            <td class="text-end fw-bold">
                                {{ number_format($factura->valor_total, 2, ',', '.') }}
                                MZN
                            </td>
                        </tr>

                    </table>

                    <div class="mt-4">

                        <div class="d-flex justify-content-between mb-2">
                            <span>Liquidação</span>
                            <strong>
                                {{ number_format($percentagem, 1, ',', '.') }}%
                            </strong>
                        </div>

                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success"
                                 style="width: {{ $percentagem }}%">
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>

    {{-- PAGAMENTOS DA FACTURA --}}
    <div class="card mt-4">

        <div class="card-header bg-white">
            <h5 class="mb-0">Pagamentos Recebidos</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Forma</th>
                            <th>Referência</th>
                            <th class="text-end">Valor Aplicado</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($factura->pagamentos as $pagamento)

                            <tr>
                                <td>#{{ $pagamento->id }}</td>

                                <td>
                                    {{ $pagamento->pago_em?->format('d/m/Y H:i') }}
                                </td>

                                <td>
                                    {{ $pagamento->forma_pagamento }}
                                </td>

                                <td>
                                    {{ $pagamento->referencia ?? '-' }}
                                </td>

                                <td class="text-end text-success fw-bold">
                                    {{ number_format(
                                        $pagamento->pivot->valor_aplicado,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                    MZN
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="5"
                                    class="text-center text-muted py-4">
                                    Esta factura ainda não possui pagamentos.
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