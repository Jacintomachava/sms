
@extends('layouts.app')

@section('conteudo')

@php
    $moeda = fn($valor, $codigo = 'MZN') =>
        number_format((float) $valor, 2, ',', '.') . ' ' . $codigo;

    $numero = fn($valor) =>
        number_format((int) $valor, 0, ',', '.');

    $data = fn($valor) => $valor
        ? \Carbon\Carbon::parse($valor)->format('d/m/Y H:i:s')
        : '—';
@endphp

<div class="container-fluid">

    {{-- CABEÇALHO --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">

            <div>
                <h5 class="mb-1">
                    <i class="fa fa-building"></i>
                    {{ $conta->nome }}
                </h5>

                <small class="text-muted">
                    Gestão administrativa da conta #{{ $conta->id }}
                </small>
            </div>

            <a href="{{ route('admin.contas.index') }}"
               class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i>
                Voltar
            </a>

        </div>
    </div>

    {{-- INDICADORES --}}
    <div class="row">

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6>Saldo SMS</h6>
                    <h4 class="text-primary">
                        {{ $carteira ? $numero($carteira->saldo_sms) : '—' }}
                    </h4>
                    <small>Saldo registado na carteira</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6>SMS Reservados</h6>
                    <h4>
                        {{ $carteira ? $numero($carteira->saldo_reservado_sms) : '—' }}
                    </h4>
                    <small>Créditos reservados</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6>Tarifa Associada</h6>
                    <h4>
                        {{ $tarifa ? $moeda($tarifa->preco_sms) : '—' }}
                    </h4>
                    <small>
                        {{ $tarifa->nome ?? 'Sem tarifa associada' }}
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6>Modalidade de Cobrança</h6>

                    <h4>
                        @if($conta->tipo_cobranca === 'PRE_PAGO')
                            <span class="badge badge-light-primary">
                                Pré-pago
                            </span>
                        @elseif($conta->tipo_cobranca === 'POS_PAGO')
                            <span class="badge badge-light-success">
                                Pós-pago
                            </span>
                        @else
                            {{ $conta->tipo_cobranca ?? '—' }}
                        @endif
                    </h4>

                    <small>{{ $conta->estado }}</small>
                </div>
            </div>
        </div>

    </div>

    {{-- DADOS DA CONTA --}}
    <div class="card">

        <div class="card-header">
            <h5>Informações da Conta</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered">

                    <tbody>
                        <tr>
                            <th width="25%">Nome</th>
                            <td>{{ $conta->nome }}</td>
                        </tr>

                        <tr>
                            <th>Nome Legal</th>
                            <td>{{ $conta->nome_legal ?? '—' }}</td>
                        </tr>

                        <tr>
                            <th>Tipo</th>
                            <td>{{ $conta->tipo }}</td>
                        </tr>

                        <tr>
                            <th>NUIT</th>
                            <td>{{ $conta->nuit ?? '—' }}</td>
                        </tr>

                        <tr>
                            <th>Email</th>
                            <td>{{ $conta->email ?? '—' }}</td>
                        </tr>

                        <tr>
                            <th>Telefone</th>
                            <td>{{ $conta->telefone ?? '—' }}</td>
                        </tr>

                        <tr>
                            <th>Estado</th>
                            <td>{{ $conta->estado }}</td>
                        </tr>

                        <tr>
                            <th>Registada em</th>
                            <td>{{ $data($conta->created_at) }}</td>
                        </tr>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    {{-- CONFIGURAÇÃO FINANCEIRA --}}
    <div class="card">

        <div class="card-header">
            <h5>Configuração Financeira</h5>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6">
                    <h6>Tarifa Actual</h6>

                    <p>
                        {{ $tarifa->nome ?? 'Sem tarifa associada' }}
                    </p>

                    @if($tarifa)
                        <p>
                            <strong>Preço:</strong>
                            {{ $moeda($tarifa->preco_sms) }}
                        </p>

                        <p>
                            <strong>Tipo:</strong>
                            {{ $tarifa->conta_id ? 'Exclusiva' : 'Geral' }}
                        </p>
                    @endif
                </div>

                <div class="col-md-6">
                    <h6>Modalidade de Cobrança</h6>

                    <p>
                        {{ $conta->tipo_cobranca }}
                    </p>

                    <h6>Estado da Carteira</h6>

                    <p>
                        {{ $carteira->estado ?? 'Sem carteira' }}
                    </p>
                </div>

            </div>

            <hr>

            <h6>Acções Administrativas</h6>

            <div class="d-flex flex-wrap gap-2">

                <button type="button"
                        class="btn btn-primary btn-sm"
                        disabled>
                    <i class="fa fa-pencil"></i>
                    Editar Conta
                </button>

                <button type="button"
                        class="btn btn-info btn-sm"
                        disabled>
                    <i class="fa fa-tags"></i>
                    Alterar Tarifa
                </button>

                <button type="button"
                        class="btn btn-warning btn-sm"
                        disabled>
                    <i class="fa fa-exchange"></i>
                    Alterar Cobrança
                </button>

                <button type="button"
                        class="btn btn-success btn-sm"
                        disabled>
                    <i class="fa fa-plus-circle"></i>
                    Recarregar SMS
                </button>

            </div>

            <small class="text-muted d-block mt-2">
                As operações administrativas serão activadas
                depois de integrar as validações e os serviços financeiros.
            </small>

        </div>
    </div>

    {{-- ÚLTIMOS PAGAMENTOS --}}
    <div class="card">

        <div class="card-header">
            <h5>Últimos 3 Pagamentos</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Forma</th>
                            <th>Referência</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($pagamentos as $pagamento)
                            <tr>
                                <td>{{ $pagamento->id }}</td>
                                <td>{{ $pagamento->forma_pagamento }}</td>
                                <td>{{ $pagamento->referencia ?? '—' }}</td>
                                <td>
                                    {{ $moeda($pagamento->valor, $pagamento->moeda ?? 'MZN') }}
                                </td>
                                <td>
                                    {{ $data($pagamento->pago_em ?? $pagamento->paga_em ?? $pagamento->created_at) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">
                                    Nenhum pagamento encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    {{-- FACTURAS --}}
    @if($conta->tipo_cobranca === 'POS_PAGO')

        <div class="card">

            <div class="card-header">
                <h5>Últimas 3 Facturas</h5>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">

                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Período</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <th>Vencimento</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($facturas as $factura)
                                <tr>
                                    <td>{{ $factura->numero }}</td>
                                    <td>
                                        {{ $factura->periodo_inicio }}
                                        até
                                        {{ $factura->periodo_fim }}
                                    </td>
                                    <td>
                                        {{ $moeda($factura->valor_total, $factura->moeda ?? 'MZN') }}
                                    </td>
                                    <td>{{ $factura->estado }}</td>
                                    <td>
                                        {{ $factura->data_vencimento
                                            ? \Carbon\Carbon::parse($factura->data_vencimento)->format('d/m/Y')
                                            : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        Nenhuma factura encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    @else

        {{-- COMPRAS DE SMS --}}
        <div class="card">

            <div class="card-header">
                <h5>Últimas 3 Compras de SMS</h5>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Quantidade</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <th>Data</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($compras as $compra)
                                <tr>
                                    <td>{{ $compra->id }}</td>
                                    <td>{{ $numero($compra->quantidade_sms) }}</td>
                                    <td>
                                        {{ $moeda($compra->valor_total, $compra->moeda ?? 'MZN') }}
                                    </td>
                                    <td>{{ $compra->estado }}</td>
                                    <td>{{ $data($compra->created_at) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        Nenhuma compra encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    @endif

    {{-- LOTES SMS --}}
    <div class="card">

        <div class="card-header">
            <h5>Últimos Lotes de SMS</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Origem</th>
                            <th>Quantidade</th>
                            <th>Disponível</th>
                            <th>Reservada</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($lotes as $lote)
                            <tr>
                                <td>{{ $lote->id }}</td>
                                <td>{{ $lote->origem }}</td>
                                <td>{{ $numero($lote->quantidade_sms) }}</td>
                                <td>{{ $numero($lote->quantidade_disponivel) }}</td>
                                <td>{{ $numero($lote->quantidade_reservada) }}</td>
                                <td>{{ $lote->estado }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    Nenhum lote encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    {{-- SENDER IDS --}}
    <div class="card">

        <div class="card-header">
            <h5>Sender IDs Associados</h5>
        </div>

        <div class="card-body">

            <p class="text-muted">
                O relacionamento entre Sender IDs e contas
                ainda precisa de ser confirmado.
            </p>

        </div>
    </div>

    {{-- UTILIZADORES --}}
    <div class="card">

        <div class="card-header">
            <h5>Utilizadores Associados</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped">

                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Role</th>
                            <th>Estado</th>
                            <th>Último Login</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($utilizadores as $utilizador)
                            <tr>
                                <td>{{ $utilizador->name }}</td>
                                <td>{{ $utilizador->email }}</td>
                                <td>{{ $utilizador->telefone ?? '—' }}</td>
                                <td>{{ $utilizador->role_nome ?? '—' }}</td>
                                <td>{{ $utilizador->estado_associacao }}</td>
                                <td>{{ $data($utilizador->ultimo_login_em) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    Nenhum utilizador associado.
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
