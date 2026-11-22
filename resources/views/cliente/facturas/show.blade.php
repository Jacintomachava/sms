
@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4>Detalhes da Factura</h4>
            <span class="text-muted">{{ $factura->numero }}</span>
        </div>

        <div>
            <a href="{{ route('cliente.facturas.pdf', $factura->id) }}"
               class="btn btn-danger">
                Descarregar PDF
            </a>

            <a href="{{ route('cliente.facturas.index') }}"
               class="btn btn-outline-info">
                Voltar às Facturas
            </a>
        </div>

    </div>

    <div class="row">

        <div class="col-lg-7 mb-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white">
                    Informações da Factura
                </div>

                <div class="card-body">

                    <table class="table table-borderless">

                        <tr>
                            <th>Número</th>
                            <td>{{ $factura->numero }}</td>
                        </tr>

                        <tr>
                            <th>Conta</th>
                            <td>{{ $factura->conta->nome }}</td>
                        </tr>

                        <tr>
                            <th>Período</th>
                            <td>
                                {{ \Carbon\Carbon::parse($factura->periodo_inicio)->format('d/m/Y') }}
                                até
                                {{ \Carbon\Carbon::parse($factura->periodo_fim)->format('d/m/Y') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Data de Emissão</th>
                            <td>
                                {{ \Carbon\Carbon::parse($factura->data_emissao)->format('d/m/Y') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Data de Vencimento</th>
                            <td>
                                {{ \Carbon\Carbon::parse($factura->data_vencimento)->format('d/m/Y') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Estado</th>
                            <td>{{ $factura->estado }}</td>
                        </tr>

                    </table>

                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white">
                    Resumo do Consumo
                </div>

                <div class="card-body">

                    <table class="table table-borderless">

                        <tr>
                            <th>SMS</th>
                            <td class="text-end">
                                {{ number_format($factura->quantidade_sms ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Segmentos</th>
                            <td class="text-end">
                                {{ number_format($factura->segmentos ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Preço por segmento</th>
                            <td class="text-end">
                                {{ number_format((float) ($factura->preco_unitario ?? 0), 4, ',', '.') }}
                                MZN
                            </td>
                        </tr>

                        <tr>
                            <th>Valor Total</th>
                            <td class="text-end">
                                <strong>
                                    {{ number_format((float) $factura->valor_total, 2, ',', '.') }}
                                    MZN
                                </strong>
                            </td>
                        </tr>

                    </table>

                    @php
                        $percentual = (float) $factura->valor_total > 0
                            ? min(100, ((float) $totalPago / (float) $factura->valor_total) * 100)
                            : 0;
                    @endphp

                    <div class="mt-4">

                        <div class="d-flex justify-content-between mb-2">
                            <span>Liquidação</span>
                            <strong>{{ number_format($percentual, 1, ',', '.') }}%</strong>
                        </div>

                        <div class="progress">
                            <div class="progress-bar bg-success"
                                 style="width: {{ $percentual }}%">
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">
            Pagamentos Recebidos
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover">

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
                                    {{ $pagamento->pago_em
                                        ? \Carbon\Carbon::parse($pagamento->pago_em)->format('d/m/Y H:i')
                                        : '-' }}
                                </td>

                                <td>{{ $pagamento->forma_pagamento }}</td>

                                <td>{{ $pagamento->referencia ?? '-' }}</td>

                                <td class="text-end text-success">
                                    {{ number_format(
                                        (float) $pagamento->pivot->valor_aplicado,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                    MZN
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="text-center">
                                    Nenhum pagamento registado.
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
