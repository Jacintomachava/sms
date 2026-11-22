
@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="mb-4">
        <h4>Minhas Facturas</h4>
        <p class="text-muted">
            Consulte as facturas da sua conta pós-paga.
        </p>
    </div>

    <div class="row mb-4">

        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Total Facturado</small>
                    <h4 class="mt-2">
                        {{ number_format((float) $totalFacturado, 2, ',', '.') }}
                        MZN
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Total Pago</small>
                    <h4 class="mt-2 text-success">
                        {{ number_format((float) $totalPago, 2, ',', '.') }}
                        MZN
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Saldo Pendente</small>
                    <h4 class="mt-2 text-danger">
                        {{ number_format((float) $saldoPendente, 2, ',', '.') }}
                        MZN
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <form method="GET"
                  action="{{ route('cliente.facturas.index') }}">

                <div class="row align-items-end">

                    <div class="col-md-4">
                        <label>Estado</label>

                        <select name="estado" class="form-control">
                            <option value="">Todos</option>

                            @foreach(['PENDENTE', 'PAGA', 'VENCIDA'] as $estado)
                                <option value="{{ $estado }}"
                                    @selected(request('estado') === $estado)>
                                    {{ $estado }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit"
                                class="btn btn-info btn-block">
                            Filtrar
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Período</th>
                            <th>Valor</th>
                            <th>Pago</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Acções</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($facturas as $factura)

                            @php
                                $pago = (string) ($factura->total_pago ?? '0.00');

                                $saldo = bcsub(
                                    (string) $factura->valor_total,
                                    $pago,
                                    2
                                );

                                if (bccomp($saldo, '0.00', 2) < 0) {
                                    $saldo = '0.00';
                                }
                            @endphp

                            <tr>

                                <td>
                                    <strong>{{ $factura->numero }}</strong>
                                </td>

                                <td>
                                    {{ \Carbon\Carbon::parse($factura->periodo_inicio)->format('d/m/Y') }}
                                    <br>
                                    <small class="text-muted">
                                        até
                                        {{ \Carbon\Carbon::parse($factura->periodo_fim)->format('d/m/Y') }}
                                    </small>
                                </td>

                                <td>
                                    {{ number_format((float) $factura->valor_total, 2, ',', '.') }}
                                    MZN
                                </td>

                                <td class="text-success">
                                    {{ number_format((float) $pago, 2, ',', '.') }}
                                    MZN
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format((float) $saldo, 2, ',', '.') }}
                                        MZN
                                    </strong>
                                </td>

                                <td>
                                    <span class="badge
                                        {{ $factura->estado === 'PAGA'
                                            ? 'bg-success'
                                            : 'bg-warning' }}">
                                        {{ $factura->estado }}
                                    </span>
                                </td>

                                <td>
                                    <a href="{{ route('cliente.facturas.show', $factura->id) }}"
                                       class="btn btn-outline-info btn-sm">
                                        Ver
                                    </a>

                                    <a href="{{ route('cliente.facturas.pdf', $factura->id) }}"
                                       class="btn btn-outline-danger btn-sm">
                                        PDF
                                    </a>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    Nenhuma factura encontrada.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{ $facturas->links() }}

        </div>
    </div>

</div>

@endsection
