@extends('layouts.app')

@section('conteudo')

<div class="container-fluid py-4">

    {{-- CABEÇALHO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">Histórico de Pagamentos</h4>
            <p class="text-muted mb-0">
                Pagamentos recebidos de contas pós-pagas
            </p>
        </div>

        <a href="{{ route('admin.facturas.index') }}"
           class="btn btn-outline-secondary">
            Voltar às Facturas
        </a>

    </div>

    {{-- FILTROS --}}
    <div class="card mb-4">
        <div class="card-body">

            <form method="GET"
                  action="{{ route('admin.pagamentos.index') }}">

                <div class="row g-3">

                    <div class="col-md-5">

                        <label class="form-label">Conta</label>

                        <select name="conta_id"
                                class="form-select">

                            <option value="">Todas as contas</option>

                            @foreach($contas as $conta)

                                <option value="{{ $conta->id }}"
                                    {{ request('conta_id') == $conta->id ? 'selected' : '' }}>
                                    {{ $conta->nome }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Forma de pagamento
                        </label>

                        <select name="forma_pagamento"
                                class="form-select">

                            <option value="">Todas</option>

                            @foreach(['CASH', 'TRANSFERENCIA', 'DEPOSITO', 'MPESA'] as $forma)

                                <option value="{{ $forma }}"
                                    {{ request('forma_pagamento') === $forma ? 'selected' : '' }}>
                                    {{ $forma }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-3 d-flex align-items-end">

                        <button type="submit"
                                class="btn btn-primary w-100">
                            Filtrar
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    {{-- LISTAGEM --}}
    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Conta</th>
                            <th>Data</th>
                            <th>Forma</th>
                            <th>Referência</th>
                            <th>Facturas</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($pagamentos as $pagamento)

                            <tr>

                                <td>
                                    #{{ $pagamento->id }}
                                </td>

                                <td>
                                    {{ $pagamento->conta->nome ?? '-' }}
                                </td>

                                <td>
                                    {{ $pagamento->pago_em?->format('d/m/Y H:i') }}
                                </td>

                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $pagamento->forma_pagamento }}
                                    </span>
                                </td>

                                <td>
                                    {{ $pagamento->referencia ?? '-' }}
                                </td>

                                <td>

                                    @foreach($pagamento->facturas as $factura)

                                        <div class="mb-1">

                                            <a href="{{ route('admin.facturas.show', $factura->id) }}">
                                                {{ $factura->numero }}
                                            </a>

                                            <small class="text-muted">
                                                ({{ number_format(
                                                    $factura->pivot->valor_aplicado,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }} MZN)
                                            </small>

                                        </div>

                                    @endforeach

                                </td>

                                <td class="text-end fw-bold text-success">
                                    {{ number_format($pagamento->valor, 2, ',', '.') }}
                                    MZN
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7"
                                    class="text-center text-muted py-4">
                                    Nenhum pagamento encontrado.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-3">
                {{ $pagamentos->links() }}
            </div>

        </div>

    </div>

</div>

@endsection