
@extends('layouts.app')

@section('conteudo')

@php
    $moeda = fn($valor, $codigo = 'MZN') =>
        number_format((float) $valor, 2, ',', '.') . ' ' . $codigo;

    $data = fn($valor) => $valor
        ? \Carbon\Carbon::parse($valor)->format('d/m/Y H:i:s')
        : '—';

    $nomeConta = $pagamento->conta_nome_legal
        ?: ($pagamento->conta_nome ?: 'Conta #' . $pagamento->conta_id);
@endphp

<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">

            <h5>
                <i class="fa fa-money"></i>
                Detalhes do Pagamento #{{ $pagamento->id }}
            </h5>

            <a href="{{ route('admin.pagamentos.index') }}"
               class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i>
                Voltar
            </a>

        </div>
    </div>

    {{-- RESUMO --}}
    <div class="row">

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Valor do Pagamento</h6>

                    <h4 class="text-primary">
                        {{ $moeda($pagamento->valor, $pagamento->moeda ?? 'MZN') }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Conta</h6>
                    <h5>{{ $nomeConta }}</h5>
                    <small>{{ $pagamento->tipo_cobranca ?? '—' }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Estado da Transacção</h6>

                    <h5>
                        {{ $transacao->estado ?? 'Sem transacção associada' }}
                    </h5>

                    <small>
                        {{ $pagamento->forma_pagamento ?? '—' }}
                    </small>
                </div>
            </div>
        </div>

    </div>

    {{-- DADOS DO PAGAMENTO --}}
    <div class="card">

        <div class="card-header">
            <h5>Informações do Pagamento</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered">

                    <tbody>
                        <tr>
                            <th width="25%">ID</th>
                            <td>{{ $pagamento->id }}</td>
                        </tr>

                        <tr>
                            <th>Conta</th>
                            <td>{{ $nomeConta }}</td>
                        </tr>

                        <tr>
                            <th>Tipo registado</th>
                            <td>{{ $pagamento->tipo ?? '—' }}</td>
                        </tr>

                        <tr>
                            <th>Forma de Pagamento</th>
                            <td>{{ $pagamento->forma_pagamento ?? '—' }}</td>
                        </tr>

                        <tr>
                            <th>Referência</th>
                            <td>{{ $pagamento->referencia ?? '—' }}</td>
                        </tr>

                        <tr>
                            <th>Valor</th>
                            <td>
                                {{ $moeda($pagamento->valor, $pagamento->moeda ?? 'MZN') }}
                            </td>
                        </tr>

                        <tr>
                            <th>Data de Registo</th>
                            <td>{{ $data($pagamento->created_at) }}</td>
                        </tr>

                        <tr>
                            <th>Data de Pagamento</th>
                            <td>
                                {{ $data($pagamento->pago_em ?? $pagamento->paga_em) }}
                            </td>
                        </tr>

                        <tr>
                            <th>Confirmado por</th>
                            <td>{{ $pagamento->confirmado_por ?? '—' }}</td>
                        </tr>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    {{-- COMPRA DE SMS --}}
    @if($compra)

        <div class="card">

            <div class="card-header">
                <h5>
                    <i class="fa fa-envelope"></i>
                    Compra de SMS #{{ $compra->id }}
                </h5>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered">

                        <tbody>

                            <tr>
                                <th width="25%">Quantidade de SMS</th>
                                <td>
                                    {{ number_format($compra->quantidade_sms, 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr>
                                <th>Preço Unitário</th>
                                <td>
                                    {{ $moeda($compra->preco_unitario, $compra->moeda) }}
                                </td>
                            </tr>

                            <tr>
                                <th>Valor da Compra</th>
                                <td>
                                    {{ $moeda($compra->valor_total, $compra->moeda) }}
                                </td>
                            </tr>

                            <tr>
                                <th>Estado da Compra</th>
                                <td>{{ $compra->estado }}</td>
                            </tr>

                            <tr>
                                <th>Créditos Disponibilizados em</th>
                                <td>{{ $data($compra->creditada_em) }}</td>
                            </tr>

                        </tbody>

                    </table>
                </div>

            </div>

        </div>

    @endif

    {{-- FACTURAS ASSOCIADAS --}}
    @if($facturas->isNotEmpty())

        <div class="card">

            <div class="card-header">
                <h5>
                    <i class="fa fa-file-text-o"></i>
                    Facturas Associadas
                </h5>
            </div>

            <div class="card-body">

                <div class="table-responsive custom-scrollbar">

                    <table class="table table-bordered table-striped">

                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Valor Total</th>
                                <th>Valor Aplicado</th>
                                <th>Estado</th>
                                <th>Vencimento</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($facturas as $factura)

                                <tr>

                                    <td>{{ $factura->numero }}</td>

                                    <td>
                                        {{ $moeda($factura->valor_total, $factura->moeda) }}
                                    </td>

                                    <td>
                                        {{ $moeda($factura->valor_aplicado, $factura->moeda) }}
                                    </td>

                                    <td>{{ $factura->estado }}</td>

                                    <td>
                                        {{ $factura->data_vencimento
                                            ? \Carbon\Carbon::parse($factura->data_vencimento)->format('d/m/Y')
                                            : '—' }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="2">Total Aplicado</th>
                                <th colspan="3">
                                    {{ $moeda($totalAplicado, $pagamento->moeda ?? 'MZN') }}
                                </th>
                            </tr>
                        </tfoot>

                    </table>

                </div>

            </div>

        </div>

    @endif

    {{-- TRANSACÇÃO --}}
    @if($transacao)

        <div class="card">

            <div class="card-header">
                <h5>
                    <i class="fa fa-exchange"></i>
                    Dados da Transacção
                </h5>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered">

                        <tbody>

                            <tr>
                                <th width="25%">ID da Transacção</th>
                                <td>{{ $transacao->id }}</td>
                            </tr>

                            <tr>
                                <th>Provider</th>
                                <td>{{ $transacao->provider ?? '—' }}</td>
                            </tr>

                            <tr>
                                <th>Forma de Pagamento</th>
                                <td>{{ $transacao->forma_pagamento ?? '—' }}</td>
                            </tr>

                            <tr>
                                <th>Estado</th>
                                <td>{{ $transacao->estado ?? '—' }}</td>
                            </tr>

                            <tr>
                                <th>ID no Provider</th>
                                <td>
                                    {{ $transacao->provider_transacao_id ?? '—' }}
                                </td>
                            </tr>

                            <tr>
                                <th>Código do Provider</th>
                                <td>{{ $transacao->provider_codigo ?? '—' }}</td>
                            </tr>

                            <tr>
                                <th>Descrição</th>
                                <td>{{ $transacao->provider_descricao ?? '—' }}</td>
                            </tr>

                            <tr>
                                <th>Referência Externa</th>
                                <td>{{ $transacao->referencia_externa ?? '—' }}</td>
                            </tr>

                            <tr>
                                <th>Banco</th>
                                <td>{{ $transacao->banco ?? '—' }}</td>
                            </tr>

                            <tr>
                                <th>Processada em</th>
                                <td>{{ $data($transacao->processada_em) }}</td>
                            </tr>

                            <tr>
                                <th>Confirmada em</th>
                                <td>{{ $data($transacao->confirmada_em) }}</td>
                            </tr>

                            <tr>
                                <th>Erro Técnico</th>
                                <td>{{ $transacao->erro_tecnico ?? '—' }}</td>
                            </tr>

                        </tbody>

                    </table>
                </div>

            </div>

        </div>

    @endif

</div>

@endsection
