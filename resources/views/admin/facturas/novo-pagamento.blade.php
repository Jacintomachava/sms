@extends('layouts.app')

@section('conteudo')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">Registar Pagamento</h4>
            <p class="text-muted mb-0">
                Liquidação de uma ou várias facturas SMS
            </p>
        </div>

        <a href="{{ route('admin.pagamentos.index') }}"
           class="btn btn-outline-secondary">
            Histórico de Pagamentos
        </a>

    </div>

    {{-- SELECCIONAR CONTA --}}

    <div class="card mb-4">
        <div class="card-body">

            <form method="GET"
                  action="{{ route('admin.pagamentos.novo') }}">

                <div class="row g-3 align-items-end">

                    <div class="col-md-9">

                        <label class="form-label">
                            Seleccionar Conta
                        </label>

                        <select name="conta_id"
                                class="form-select"
                                required>

                            <option value="">
                                Seleccione uma conta
                            </option>

                            @foreach($contas as $conta)

                                <option value="{{ $conta->id }}"
                                    @selected($contaId == $conta->id)>
                                    {{ $conta->nome }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-3">

                        <button type="submit"
                                class="btn btn-primary w-100">
                            Consultar Facturas
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    @if($contaId > 0)

        <form id="formPagamentoMultiplo">

            @csrf

            <input type="hidden"
                   name="conta_id"
                   value="{{ $contaId }}">

            <input type="hidden"
                   name="chave_idempotencia"
                   id="chave_idempotencia">

            {{-- FACTURAS --}}

            <div class="card mb-4">

                <div class="card-header bg-white">
                    <h5 class="mb-0">Facturas Pendentes</h5>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Seleccionar</th>
                                    <th>Factura</th>
                                    <th>Vencimento</th>
                                    <th>Valor Total</th>
                                    <th>Pago</th>
                                    <th>Saldo</th>
                                    <th>Valor a Pagar</th>
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
                                    @endphp

                                    <tr>

                                        <td>
                                            <input type="checkbox"
                                                   class="form-check-input check-factura"
                                                   data-id="{{ $factura->id }}"
                                                   data-saldo="{{ $saldo }}">
                                        </td>

                                        <td>
                                            <strong>{{ $factura->numero }}</strong>
                                        </td>

                                        <td>
                                            {{ $factura->data_vencimento?->format('d/m/Y') ?? '-' }}
                                        </td>

                                        <td>
                                            {{ number_format($factura->valor_total, 2, ',', '.') }}
                                        </td>

                                        <td class="text-success">
                                            {{ number_format($pago, 2, ',', '.') }}
                                        </td>

                                        <td class="fw-bold">
                                            {{ number_format($saldo, 2, ',', '.') }}
                                        </td>

                                        <td>
                                            <input type="number"
                                                   class="form-control valor-factura"
                                                   data-id="{{ $factura->id }}"
                                                   data-saldo="{{ $saldo }}"
                                                   min="0.01"
                                                   max="{{ $saldo }}"
                                                   step="0.01"
                                                   value="{{ $saldo }}"
                                                   disabled
                                                   style="min-width:130px">
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7"
                                            class="text-center text-muted py-4">
                                            Nenhuma factura pendente.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            @if($facturas->isNotEmpty())

                {{-- DADOS DO PAGAMENTO --}}

                <div class="card">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">Dados do Pagamento</h5>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-4">

                                <label class="form-label">
                                    Forma de Pagamento
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

                            <div class="col-md-4">

                                <label class="form-label">
                                    Referência
                                </label>

                                <input type="text"
                                       name="referencia"
                                       class="form-control"
                                       maxlength="150">

                            </div>

                            <div class="col-md-4">

                                <label class="form-label">
                                    Total a Pagar
                                </label>

                                <input type="text"
                                       id="totalPagamento"
                                       class="form-control fw-bold text-success"
                                       value="0.00 MZN"
                                       readonly>

                            </div>

                        </div>

                        <div class="alert alert-warning mt-4 mb-3">
                            Confirme que o dinheiro foi efectivamente recebido
                            antes de registar o pagamento.
                        </div>

                        <div class="text-end">

                            <button type="submit"
                                    id="btnConfirmarPagamento"
                                    class="btn btn-success">
                                Confirmar Pagamento
                            </button>

                        </div>

                    </div>

                </div>

            @endif

        </form>

    @endif

</div>

@endsection

@section('script')

<script>
$(function () {

    const chaveInput = $('#chave_idempotencia');

    if (chaveInput.length) {
        chaveInput.val(crypto.randomUUID());
    }

    /*
    |--------------------------------------------------------------------------
    | SELECCIONAR FACTURA
    |--------------------------------------------------------------------------
    */

    $('.check-factura').on('change', function () {

        const id = $(this).data('id');

        const input = $('.valor-factura[data-id="' + id + '"]');

        input.prop('disabled', !this.checked);

        if (this.checked) {
            input.val(input.data('saldo'));
        }

        calcularTotal();
    });

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR TOTAL
    |--------------------------------------------------------------------------
    */

    $('.valor-factura').on('input', calcularTotal);

    function calcularTotal() {

        let totalCentavos = 0;

        $('.check-factura:checked').each(function () {

            const id = $(this).data('id');

            const input = $('.valor-factura[data-id="' + id + '"]');

            const valor = String(input.val()).trim();

            if (/^\d+(\.\d{1,2})?$/.test(valor)) {
                const partes = valor.split('.');
                totalCentavos +=
                    Number(partes[0]) * 100 +
                    Number((partes[1] || '').padEnd(2, '0'));
            }
        });

        $('#totalPagamento').val(
            (totalCentavos / 100).toFixed(2) + ' MZN'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SUBMISSÃO
    |--------------------------------------------------------------------------
    */

    $('#formPagamentoMultiplo').on('submit', function (e) {

        e.preventDefault();

        const form = $(this);
        const facturas = [];

        let erro = null;

        $('.check-factura:checked').each(function () {

            const id = $(this).data('id');

            const input = $('.valor-factura[data-id="' + id + '"]');

            const valor = String(input.val()).trim();
            const saldo = Number(input.data('saldo'));

            if (
                !/^\d+(\.\d{1,2})?$/.test(valor) ||
                Number(valor) <= 0 ||
                Number(valor) > saldo
            ) {
                erro = 'Verifique os valores das facturas seleccionadas.';
                return false;
            }

            facturas.push({
                factura_id: id,
                valor: valor
            });
        });

        if (erro) {
            Swal.fire('Atenção', erro, 'warning');
            return;
        }

        if (facturas.length === 0) {
            Swal.fire(
                'Atenção',
                'Seleccione pelo menos uma factura.',
                'warning'
            );
            return;
        }

        const forma = form.find('[name="forma_pagamento"]').val();

        if (!forma) {
            Swal.fire(
                'Atenção',
                'Seleccione a forma de pagamento.',
                'warning'
            );
            return;
        }

        Swal.fire({
            title: 'Confirmar pagamento?',
            text: 'Confirma que o valor foi efectivamente recebido?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, confirmar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {

            if (!resultado.isConfirmed) {
                return;
            }

            const botao = $('#btnConfirmarPagamento');

            botao.prop('disabled', true);

            $.ajax({
                url: "{{ route('admin.pagamentos.store') }}",
                method: 'POST',
                dataType: 'json',

                data: {
                    _token: form.find('[name="_token"]').val(),
                    conta_id: form.find('[name="conta_id"]').val(),
                    chave_idempotencia: chaveInput.val(),
                    forma_pagamento: forma,
                    referencia: form.find('[name="referencia"]').val(),
                    facturas: facturas
                },

                success: function (response) {

                    if (response.status == 1) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Pagamento registado',
                            text: response.message
                        }).then(function () {
                            window.location.href =
                                "{{ route('admin.pagamentos.index') }}";
                        });

                    } else {

                        Swal.fire(
                            'Erro',
                            response.message,
                            'error'
                        );
                    }
                },

                error: function (xhr) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: xhr.responseJSON?.message ||
                              'Não foi possível registar o pagamento.'
                    });
                },

                complete: function () {
                    botao.prop('disabled', false);
                }
            });

        });

    });

});
</script>

@endsection