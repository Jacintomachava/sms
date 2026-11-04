
@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="card">

        <div class="card-header">
            <h5>
                <i class="fa fa-money"></i>
                Monitorização de Pagamentos
            </h5>
        </div>

        <div class="card-body">

            {{-- FILTROS --}}
            <form method="GET"
                  action="{{ route('admin.pagamentos.index') }}"
                  class="row g-3 mb-4">

                <div class="col-md-4">
                    <label class="form-label">Conta</label>

                    <select name="conta_id" class="form-select">
                        <option value="">Todas as contas</option>

                        @foreach($contas as $conta)
                            <option value="{{ $conta->id }}"
                                @selected(request('conta_id') == $conta->id)>
                                {{ $conta->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo de Pagamento</label>

                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>

                        <option value="SMS"
                            @selected(request('tipo') === 'SMS')>
                            Compra de SMS
                        </option>

                        <option value="FACTURA"
                            @selected(request('tipo') === 'FACTURA')>
                            Factura
                        </option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Data inicial</label>

                    <input type="date"
                           name="inicio"
                           value="{{ request('inicio') }}"
                           class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Data final</label>

                    <input type="date"
                           name="fim"
                           value="{{ request('fim') }}"
                           class="form-control">
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit"
                            class="btn btn-primary w-100">
                        <i class="fa fa-search"></i>
                    </button>
                </div>

                <div class="col-12">
                    <a href="{{ route('admin.pagamentos.index') }}"
                       class="btn btn-light btn-sm">
                        <i class="fa fa-refresh"></i>
                        Limpar filtros
                    </a>
                </div>

            </form>

            {{-- LISTAGEM --}}
            <div class="table-responsive">

                <table class="display" id="basic-1">

                    <thead>
                        <tr>
                            <th>Conta</th>
                            <th>Tipo</th>
                            <th>Referência</th>
                            <th>Forma</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Acções</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($pagamentos as $pagamento)

                            @php
                                $temFactura = (int) ($pagamento->total_facturas ?? 0) > 0;
                                $temCompra = !is_null($pagamento->compra_sms_id);

                                $tipo = $temFactura
                                    ? 'Factura'
                                    : ($temCompra ? 'Compra SMS' : ($pagamento->tipo ?? 'Outro'));

                                $referencia = $temFactura
                                    ? ($pagamento->numeros_facturas ?? '—')
                                    : ($pagamento->referencia
                                        ?: ($temCompra
                                            ? 'Compra #' . $pagamento->compra_sms_id
                                            : '—'));

                                $dataPagamento = $pagamento->pago_em
                                    ?? $pagamento->paga_em
                                    ?? $pagamento->created_at;
                            @endphp

                            <tr>

                                {{-- CONTA --}}
                                <td>
                                    <strong>
                                        {{ $pagamento->conta_nome ?: 'Conta #' . $pagamento->conta_id }}
                                    </strong>

                                    @if($pagamento->conta_nome_legal)
                                        <br>
                                        <small class="text-muted">
                                            {{ $pagamento->conta_nome_legal }}
                                        </small>
                                    @endif
                                </td>

                                {{-- TIPO --}}
                                <td>
                                    @if($tipo === 'Compra SMS')
                                        <span class="badge badge-light-primary">
                                            Compra SMS
                                        </span>
                                    @elseif($tipo === 'Factura')
                                        <span class="badge badge-light-success">
                                            Factura
                                        </span>
                                    @else
                                        <span class="badge badge-light-secondary">
                                            {{ $tipo }}
                                        </span>
                                    @endif
                                </td>

                                {{-- REFERÊNCIA --}}
                                <td
                                    data-container="body"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    data-bs-original-title="{{ $referencia }}"
                                    style="cursor: pointer;">

                                    {{ \Illuminate\Support\Str::limit($referencia, 25) }}
                                </td>

                                {{-- FORMA DE PAGAMENTO --}}
                                <td>
                                    {{ $pagamento->forma_pagamento ?? '—' }}
                                </td>

                                {{-- VALOR --}}
                                <td>
                                    <strong>
                                        {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                        {{ $pagamento->moeda ?? 'MZN' }}
                                    </strong>
                                </td>

                                {{-- DATA --}}
                                <td>
                                    {{ $dataPagamento
                                        ? \Carbon\Carbon::parse($dataPagamento)->format('d/m/Y')
                                        : '—' }}
                                </td>

                                {{-- HORA --}}
                                <td>
                                    {{ $dataPagamento
                                        ? \Carbon\Carbon::parse($dataPagamento)->format('H:i:s')
                                        : '—' }}
                                </td>

                                {{-- ACÇÕES --}}
                                <td>
                                    <a href="{{ route('admin.pagamentos.show', $pagamento->id) }}"
                                       class="btn btn-primary btn-xs">
                                        <i class="fa fa-eye"></i>
                                        Detalhes
                                    </a>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            {{-- PAGINAÇÃO LARAVEL --}}
            <div class="mt-3">
                {{ $pagamentos->links() }}
            </div>

        </div>

    </div>

</div>

@endsection
