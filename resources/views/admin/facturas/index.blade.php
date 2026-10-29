@extends('layouts.app')

@section('conteudo')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Facturação SMS</h4>
            <p class="text-muted mb-0">
                Gestão de facturas e pagamentos pós-pagos
            </p>
        </div>

        <a href="{{ route('admin.pagamentos.index') }}"
           class="btn btn-outline-primary">
            <i class="fas fa-history"></i>
            Histórico de Pagamentos
        </a>
    </div>

    {{-- FILTROS --}}

    <div class="card mb-4">
        <div class="card-body">

            <form method="GET"
                  action="{{ route('admin.facturas.index') }}">

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
                        <label class="form-label">Estado</label>

                        <select name="estado"
                                class="form-select">

                            <option value="">Todos</option>

                            @foreach(['PENDENTE', 'PAGA', 'VENCIDA', 'CANCELADA'] as $estado)
                                <option value="{{ $estado }}"
                                    {{ request('estado') == $estado ? 'selected' : '' }}>
                                    {{ $estado }}
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

    {{-- TABELA --}}

    <div class="card">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Conta</th>
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
                                $totalPago = (float) ($factura->total_pago ?? 0);

                                $saldo = max(
                                    0,
                                    (float) $factura->valor_total - $totalPago
                                );
                            @endphp

                            <tr>

                                <td>
                                    <strong>{{ $factura->numero }}</strong>
                                </td>

                                <td>
                                    {{ $factura->conta->nome ?? '-' }}
                                </td>

                                <td>
                                    {{ $factura->periodo_inicio?->format('d/m/Y') }}
                                    <br>
                                    <small class="text-muted">
                                        até {{ $factura->periodo_fim?->format('d/m/Y') }}
                                    </small>
                                </td>

                                <td>
                                    {{ number_format($factura->valor_total, 2, ',', '.') }}
                                    MZN
                                </td>

                                <td class="text-success">
                                    {{ number_format($totalPago, 2, ',', '.') }}
                                    MZN
                                </td>

                                <td class="fw-bold">
                                    {{ number_format($saldo, 2, ',', '.') }}
                                    MZN
                                </td>

                                <td>

                                    @if($factura->estado === 'PAGA')
                                        <span class="badge bg-success">PAGA</span>

                                    @elseif($factura->estado === 'PENDENTE')
                                        <span class="badge bg-warning text-dark">PENDENTE</span>

                                    @elseif($factura->estado === 'VENCIDA')
                                        <span class="badge bg-danger">VENCIDA</span>

                                    @else
                                        <span class="badge bg-secondary">
                                            {{ $factura->estado }}
                                        </span>
                                    @endif

                                </td>

                                <td>

                                    <a href="{{ route('admin.facturas.show', $factura->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>

                                    @if(
                                        in_array($factura->estado, ['PENDENTE', 'VENCIDA'])
                                        && $saldo > 0
                                    )

                                        <button type="button"
                                                class="btn btn-sm btn-success btn-pagar"
                                                data-id="{{ $factura->id }}"
                                                data-conta="{{ $factura->conta_id }}"
                                                data-numero="{{ $factura->numero }}"
                                                data-saldo="{{ number_format($saldo, 2, '.', '') }}">
                                            Pagar
                                        </button>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="8"
                                    class="text-center text-muted py-4">
                                    Nenhuma factura encontrada.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-3">
                {{ $facturas->links() }}
            </div>

        </div>
    </div>

</div>


{{-- MODAL PAGAMENTO --}}

<div class="modal fade"
     id="modalPagamento"
     tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formPagamento">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Registar Pagamento
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <input type="hidden"
                           name="conta_id"
                           id="pagamento_conta_id">

                    <input type="hidden"
                           name="chave_idempotencia"
                           id="pagamento_chave">

                    <input type="hidden"
                           name="facturas[0][factura_id]"
                           id="pagamento_factura_id">

                    <div class="mb-3">

                        <label class="form-label">Factura</label>

                        <input type="text"
                               id="pagamento_numero"
                               class="form-control"
                               readonly>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Saldo pendente</label>

                        <input type="text"
                               id="pagamento_saldo"
                               class="form-control"
                               readonly>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Valor recebido (MZN)
                        </label>

                        <input type="number"
                               name="facturas[0][valor]"
                               id="pagamento_valor"
                               class="form-control"
                               min="0.01"
                               step="0.01"
                               required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Forma de pagamento
                        </label>

                        <select name="forma_pagamento"
                                class="form-select"
                                required>

                            <option value="">Seleccione</option>
                            <option value="TRANSFERENCIA">Transferência</option>
                            <option value="DEPOSITO">Depósito</option>
                            <option value="CASH">Dinheiro</option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Referência
                        </label>

                        <input type="text"
                               name="referencia"
                               class="form-control"
                               maxlength="150">

                    </div>

                    <div class="alert alert-warning small mb-0">
                        Confirme que o valor foi efectivamente recebido
                        antes de registar o pagamento.
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit"
                            id="btnGuardarPagamento"
                            class="btn btn-success">
                        Confirmar Pagamento
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection


@section('scripts')

<script>
$(function () {

    const modalPagamento = new bootstrap.Modal(
        document.getElementById('modalPagamento')
    );

    function gerarUUID() {
        return crypto.randomUUID();
    }

    $('.btn-pagar').on('click', function () {

        const botao = $(this);

        $('#formPagamento')[0].reset();

        $('#pagamento_conta_id').val(botao.data('conta'));
        $('#pagamento_factura_id').val(botao.data('id'));
        $('#pagamento_numero').val(botao.data('numero'));

        $('#pagamento_saldo').val(
            Number(botao.data('saldo')).toFixed(2)
        );

        $('#pagamento_valor').val(
            Number(botao.data('saldo')).toFixed(2)
        );

        $('#pagamento_chave').val(gerarUUID());

        modalPagamento.show();
    });

    $('#formPagamento').validate({

        rules: {
            'facturas[0][valor]': {
                required: true,
                number: true,
                min: 0.01
            },
            forma_pagamento: {
                required: true
            }
        },

        submitHandler: function (form) {

            Swal.fire({
                title: 'Confirmar pagamento?',
                text: 'Confirma que o valor foi recebido?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, confirmar',
                cancelButtonText: 'Cancelar'
            }).then(function (resultado) {

                if (!resultado.isConfirmed) {
                    return;
                }

                const botao = $('#btnGuardarPagamento');

                botao.prop('disabled', true);

                $.ajax({

                    url: "{{ route('admin.pagamentos.store') }}",
                    method: 'POST',
                    data: $(form).serialize(),
                    dataType: 'json',

                    success: function (response) {

                        if (response.status == 1) {

                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso',
                                text: response.message
                            }).then(function () {
                                window.location.reload();
                            });

                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: response.message
                            });
                        }
                    },

                    error: function (xhr) {

                        let mensagem =
                            xhr.responseJSON?.message ||
                            'Não foi possível registar o pagamento.';

                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: mensagem
                        });
                    },

                    complete: function () {
                        botao.prop('disabled', false);
                    }

                });

            });

        }
    });

});
</script>

@endsection