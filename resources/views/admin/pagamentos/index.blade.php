
@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="card">

        <div class="card-header">
            <h5>
                <i class="fa fa-money"></i>
                Monitorização Global de Pagamentos
            </h5>
            <p class="mb-0">
                Consulta de pagamentos de facturas e compras de SMS.
            </p>
        </div>

        <div class="card-body">

            {{-- FILTROS --}}
            <form method="GET"
                  action="{{ route('admin.pagamentos.index') }}"
                  class="row g-3 mb-4">

                <div class="col-md-3">
                    <label class="form-label">Conta</label>

                    <select name="conta_id" class="form-select">
                        <option value="">Todas as contas</option>

                        @foreach($contas as $conta)
                            <option value="{{ $conta->id }}"
                                @selected(request('conta_id') == $conta->id)>
                                {{ $conta->nome_legal ?: $conta->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tipo</label>

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
                    <label class="form-label">Estado da transacção</label>

                    <select name="estado" class="form-select">
                        <option value="">Todos</option>

                        @foreach($estados as $estado)
                            <option value="{{ $estado }}"
                                @selected(request('estado') === $estado)>
                                {{ $estado }}
                            </option>
                        @endforeach
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

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- TABELA --}}
            <div class="table-responsive custom-scrollbar">

                <table class="display"
                       id="pagamentos-monitor-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Conta</th>
                            <th>Tipo</th>
                            <th>Referência</th>
                            <th>Forma</th>
                            <th>Valor</th>
                            <th>Estado Trans.</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Acção</th>
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
                                    ? $pagamento->numeros_facturas
                                    : ($pagamento->referencia ?: (
                                        $temCompra
                                            ? 'Compra #' . $pagamento->compra_sms_id
                                            : '—'
                                    ));

                                $estado = $pagamento->transacao_estado ?? 'Sem transacção';

                                $cor = match(strtoupper($estado)) {
                                    'SUCESSO', 'PAGO', 'CONFIRMADO', 'CONCLUIDO',
                                    'CONCLUÍDO', 'APROVADO' => 'success',

                                    'FALHADO', 'FALHADA', 'ERRO',
                                    'REJEITADO', 'CANCELADO' => 'danger',

                                    'PENDENTE', 'PROCESSANDO',
                                    'EM_PROCESSAMENTO' => 'warning',

                                    default => 'secondary'
                                };

                                $data = $pagamento->pago_em
                                    ?? $pagamento->paga_em
                                    ?? $pagamento->created_at;
                            @endphp

                            <tr>

                                <td>{{ $pagamento->id }}</td>

                                <td>
                                    <strong>
                                        {{ $pagamento->conta_nome_legal
                                            ?: ($pagamento->conta_nome
                                            ?: 'Conta #' . $pagamento->conta_id) }}
                                    </strong>
                                </td>

                                <td>
                                    <span class="badge badge-light-primary">
                                        {{ $tipo }}
                                    </span>
                                </td>

                                <td>
                                    <span data-bs-toggle="tooltip"
                                          data-bs-placement="top"
                                          data-bs-original-title="{{ $referencia }}"
                                          style="cursor: pointer;">
                                        {{ \Illuminate\Support\Str::limit($referencia, 25) }}
                                    </span>
                                </td>

                                <td>
                                    {{ $pagamento->forma_pagamento ?? '—' }}
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format($pagamento->valor, 2, ',', '.') }}
                                        {{ $pagamento->moeda ?? 'MZN' }}
                                    </strong>
                                </td>

                                <td>
                                    <span class="badge bg-{{ $cor }}">
                                        {{ $estado }}
                                    </span>
                                </td>

                                <td>
                                    {{ $data
                                        ? \Carbon\Carbon::parse($data)->format('d/m/Y')
                                        : '—' }}
                                </td>

                                <td>
                                    {{ $data
                                        ? \Carbon\Carbon::parse($data)->format('H:i:s')
                                        : '—' }}
                                </td>

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

            <div class="mt-3">
                {{ $pagamentos->links() }}
            </div>

            <small class="text-muted">
                A apresentar {{ $pagamentos->count() }}
                de {{ $pagamentos->total() }} pagamentos registados.
            </small>

        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Tooltip Bootstrap 5
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll(
            '#pagamentos-monitor-table [data-bs-toggle="tooltip"]'
        ).forEach(function (element) {
            new bootstrap.Tooltip(element, {
                container: 'body'
            });
        });
    }

    // DataTables: sem paginação local, pois o Laravel já pagina.
    if (
        window.jQuery &&
        $.fn.DataTable &&
        !$.fn.DataTable.isDataTable('#pagamentos-monitor-table')
    ) {
        $('#pagamentos-monitor-table').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            lengthChange: false
        });
    }
});
</script>
@endpush
