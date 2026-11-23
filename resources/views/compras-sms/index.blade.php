@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    {{-- CABEÇALHO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Créditos SMS</h4>
            <small class="text-muted">
                Compre e acompanhe os créditos SMS da sua conta.
            </small>
        </div>


    </div>


    {{-- CARDS --}}
<div class="row">

    {{-- SALDO ACTUAL --}}
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="widget-stat card bg-primary card-shadow">
            <div class="card-body p-4">

                <div class="media">
                    <div class="media-body text-white">

                        <p class="mb-1">
                            Saldo SMS
                        </p>

                        <h4 class="text-white">
                            {{ number_format($saldoSms, 0, ',', '.') }}
                        </h4>

                        <div class="progress mb-2 bg-secondary">
                            <div
                                class="progress-bar progress-animated bg-white"
                                style="width: 100%"
                            ></div>
                        </div>

                        <h6 class="text-white mb-0">
                            Créditos disponíveis
                        </h6>

                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- TOTAL COMPRADO --}}
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="widget-stat card bg-success card-shadow">
            <div class="card-body p-4">

                <div class="media">
                    <div class="media-body text-white">

                        <p class="mb-1">
                            Total Comprado
                        </p>

                        <h4 class="text-white">
                            {{ number_format($totalComprado, 0, ',', '.') }}
                        </h4>

                        <div class="progress mb-2 bg-primary">
                            <div
                                class="progress-bar progress-animated bg-white"
                                style="width: 100%"
                            ></div>
                        </div>

                        <h6 class="text-white mb-0">
                            SMS adquiridos
                        </h6>

                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- TOTAL INVESTIDO --}}
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="widget-stat card bg-info card-shadow">
            <div class="card-body p-4">

                <div class="media">
                    <div class="media-body text-white">

                        <p class="mb-1">
                            Total Investido
                        </p>

                        <h4 class="text-white">
                            {{ number_format(
                                $totalInvestido,
                                2,
                                ',',
                                '.'
                            ) }}
                            MT
                        </h4>

                        <div class="progress mb-2 bg-primary">
                            <div
                                class="progress-bar progress-animated bg-white"
                                style="width: 100%"
                            ></div>
                        </div>

                        <h6 class="text-white mb-0">
                            Compras pagas
                        </h6>

                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- PENDENTES --}}
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="widget-stat card bg-warning card-shadow">
            <div class="card-body p-4">

                <div class="media">
                    <div class="media-body text-white">

                        <p class="mb-1">
                            Compras Pendentes
                        </p>

                        <h4 class="text-white">
                            {{ number_format(
                                $comprasPendentes,
                                0,
                                ',',
                                '.'
                            ) }}
                        </h4>

                        <div class="progress mb-2 bg-primary">
                            <div
                                class="progress-bar progress-animated bg-white"
                                style="width: 100%"
                            ></div>
                        </div>

                        <h6 class="text-white mb-0">
                            Aguardam pagamento
                        </h6>

                    </div>
                </div>

            </div>
        </div>
    </div>

</div>


    {{-- TABELA --}}
    <div class="row">

    <div class="col-sm-12">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="mb-1">
                        Histórico de Compras
                    </h5>

                    <span class="text-muted">
                        Consulte as compras e pagamentos de créditos SMS.
                    </span>
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalComprarSms"
                >
                    <i class="fa fa-plus"></i>
                    Comprar SMS
                </button>

            </div>


            <div class="card-body">

                <div class="table-responsive custom-scrollbar">

                    <table
                        class="display"
                        id="basic-1"
                    >

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Quantidade</th>
                                <th>Preço/SMS</th>
                                <th>Total</th>
                                <th>Pagamento</th>
                                <th>Estado</th>
                                <th>Acção</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($compras as $compra)

                                <tr>

                                    <td>
                                        #{{ $compra->id }}
                                    </td>


                                    <td>
                                        {{ $compra->created_at->format('d/m/Y H:i') }}
                                    </td>


                                    <td>

                                        <strong>
                                            {{ number_format(
                                                $compra->quantidade_sms,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </strong>

                                        SMS

                                    </td>


                                    <td>

                                        {{ number_format(
                                            $compra->preco_unitario,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        MT

                                    </td>


                                    <td>

                                        <strong>

                                            {{ number_format(
                                                $compra->valor_total,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                            MT

                                        </strong>

                                    </td>


                                    <td>

                                        @if($compra->pagamento)

                                            <span class="badge bg-light text-dark">

                                                {{ $compra->pagamento->forma_pagamento }}

                                            </span>

                                        @else

                                            <span class="text-muted">
                                                -
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if($compra->estado === 'PAGA')

                                            <span class="badge bg-success">
                                                Paga
                                            </span>

                                        @elseif($compra->estado === 'PENDENTE')

                                            <span class="badge bg-warning">
                                                Pendente
                                            </span>

                                        @elseif($compra->estado === 'CANCELADA')

                                            <span class="badge bg-danger">
                                                Cancelada
                                            </span>

                                        @elseif($compra->estado === 'EXPIRADA')

                                            <span class="badge bg-secondary">
                                                Expirada
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if($compra->estado === 'PENDENTE')

                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm btn-tentar-pagamento"

                                                data-id="{{ $compra->id }}"

                                                data-quantidade="{{ $compra->quantidade_sms }}"

                                                data-total="{{ $compra->valor_total }}"

                                                title="Pagar"
                                            >
                                                <i class="fa fa-credit-card"></i>
                                            </button>

                                        @else

                                            <button
                                                type="button"
                                                class="btn btn-info btn-sm btn-detalhes-compra"

                                                data-id="{{ $compra->id }}"

                                                title="Detalhes"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </button>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

</div>


{{-- MODAL COMPRAR SMS --}}
<div class="modal fade" id="modalComprarSms" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    <i class="fa fa-shopping-cart"></i>
                    Comprar Créditos SMS
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <form id="formComprarSms">

                @csrf
                <input type="hidden" name="idempotency_key" id="compra_idempotency_key">

                <div class="modal-body">

                    {{-- QUANTIDADE --}}
                    <div class="mb-3">

                        <label class="form-label">
                            Quantidade de SMS
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="number"
                            name="quantidade_sms"
                            id="quantidade_sms"
                            class="form-control"
                            min="1"
                            placeholder="Ex.: 1000"
                            required
                        >

                        <small class="text-muted">
                            Informe a quantidade de créditos SMS que pretende comprar.
                        </small>

                    </div>


                    {{-- RESUMO DA COMPRA --}}
                    <div
                        id="resumoCompra"
                        class="border rounded p-3 mb-3 d-none"
                    >

                        <h6 class="mb-3">
                            Resumo da Compra
                        </h6>


                        <div class="d-flex justify-content-between mb-2">

                            <span class="text-muted">
                                Quantidade
                            </span>

                            <strong>
                                <span id="resumoQuantidade">0</span>
                                SMS
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span class="text-muted">
                                Tarifa aplicada
                            </span>

                            <strong id="resumoTarifa">
                                -
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span class="text-muted">
                                Preço por SMS
                            </span>

                            <strong>
                                <span id="resumoPreco">0,00</span>
                                MT
                            </strong>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between">

                            <strong>
                                Total a pagar
                            </strong>

                            <strong class="text-primary" style="font-size: 18px;">
                                <span id="resumoTotal">0,00</span>
                                MT
                            </strong>

                        </div>

                    </div>


                    {{-- TELEFONE --}}
                    <div class="mb-3">

                        <label class="form-label">
                            Número M-Pesa
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="telefone"
                            id="telefone"
                            class="form-control"
                            maxlength="9"
                            placeholder="84xxxxxxx"
                            required
                        >

                        <small class="text-muted">
                            Será enviado um pedido de pagamento para este número.
                        </small>

                    </div>


                    <div class="alert alert-light border mb-0">

                        <i class="fa fa-info-circle text-primary"></i>

                        Confirme que o número M-Pesa está correcto antes
                        de continuar.

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnComprar"
    
                    >
                        <i class="fa fa-credit-card"></i>
                        Comprar e Pagar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- MODAL PAGAR COMPRA PENDENTE --}}
<div class="modal fade" id="modalPagarCompra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pagar compra #<span id="pagarCompraId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formPagarCompra">
                @csrf
                <input type="hidden" id="compra_id" name="compra_id">
                <input type="hidden" id="pagamento_idempotency_key" name="idempotency_key">
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Quantidade</span><strong><span id="pagarQuantidade">0</span> SMS</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total a pagar</span><strong><span id="pagarTotal">0,00</span> MT</strong>
                    </div>
                    <div class="mb-3">
                        <label for="telefonePagamento" class="form-label">Número M-Pesa *</label>
                        <input type="text" class="form-control" name="telefone"
                               id="telefonePagamento" maxlength="9" inputmode="numeric"
                               pattern="(84|85)[0-9]{7}" placeholder="84xxxxxxx" required>
                    </div>
                    <div class="alert alert-light border mb-0">
                        Confirme o número e autorize o pagamento no telefone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnPagarCompra">Pagar com M-Pesa</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function () {
    'use strict';

    let timerCalculo = null;
    let calculoValido = false;
    let quantidadeCalculada = null;
    let pedidoCalculo = null;
    let compraEmCurso = false;
    let pagamentoEmCurso = false;
    let chaveCompra = null;
    let telefoneDaChave = null;
    const chavesPagamentos = {};

    const formatarNumero = valor => new Intl.NumberFormat('pt-PT', {
        maximumFractionDigits: 0
    }).format(valor);

    const formatarDinheiro = valor => new Intl.NumberFormat('pt-PT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);

    function novaChave() {
        if (!window.crypto || !window.crypto.randomUUID) {
            throw new Error('É necessária uma ligação HTTPS para iniciar um pagamento seguro.');
        }
        return window.crypto.randomUUID();
    }

    function mensagemErro(xhr, padrao) {
        const erros = xhr.responseJSON?.errors;
        const primeiroErro = erros ? Object.values(erros).flat()[0] : null;
        return primeiroErro || xhr.responseJSON?.message || padrao;
    }

    function validarTelefone(telefone) {
        return /^(84|85)[0-9]{7}$/.test(telefone);
    }

    function invalidarCalculo() {
        calculoValido = false;
        quantidadeCalculada = null;
        $('#btnComprar').prop('disabled', true);
        $('#resumoCompra').addClass('d-none');
    }

    $('#quantidade_sms').on('input', function () {
        clearTimeout(timerCalculo);
        if (pedidoCalculo) pedidoCalculo.abort();
        invalidarCalculo();
        // Alterar a quantidade significa uma nova operação de compra.
        chaveCompra = null;
        telefoneDaChave = null;
        $('#compra_idempotency_key').val('');

        const quantidade = Number($(this).val());
        if (!Number.isSafeInteger(quantidade) || quantidade < 1) return;

        timerCalculo = setTimeout(function () {
            pedidoCalculo = $.ajax({
                url: @json(route('compras.sms.calcular')),
                method: 'POST',
                data: {
                    _token: @json(csrf_token()),
                    quantidade_sms: quantidade
                },
                success: function (response) {
                    if (Number($('#quantidade_sms').val()) !== quantidade) return;
                    if (Number(response.status) !== 1 || !response.data) {
                        invalidarCalculo();
                        return;
                    }
                    const dados = response.data;
                    $('#resumoQuantidade').text(formatarNumero(dados.quantidade_sms));
                    $('#resumoTarifa').text(dados.tarifa || '-');
                    $('#resumoPreco').text(formatarDinheiro(dados.preco_unitario));
                    $('#resumoTotal').text(formatarDinheiro(dados.valor_total));
                    $('#resumoCompra').removeClass('d-none');
                    quantidadeCalculada = quantidade;
                    calculoValido = true;
                    $('#btnComprar').prop('disabled', false);
                },
                error: function (xhr, estado) {
                    if (estado === 'abort') return;
                    invalidarCalculo();
                    Swal.fire('Erro', mensagemErro(xhr, 'Não foi possível calcular a tarifa.'), 'error');
                }
            });
        }, 500);
    });

    $('#formComprarSms').on('submit', function (e) {
        e.preventDefault();
        if (compraEmCurso || !calculoValido) return;
        if (Number($('#quantidade_sms').val()) !== quantidadeCalculada) {
            invalidarCalculo();
            return;
        }
        const telefone = $('#telefone').val().trim();
        if (!validarTelefone(telefone)) {
            Swal.fire('Telefone inválido', 'Introduza um número M-Pesa com 9 dígitos, iniciado por 84 ou 85.', 'warning');
            return;
        }
        // Uma chave representa exactamente a mesma quantidade e telefone.
        if (chaveCompra && telefoneDaChave !== telefone) {
            chaveCompra = null;
        }
        try {
            chaveCompra = chaveCompra || novaChave();
            telefoneDaChave = telefone;
        } catch (erro) {
            Swal.fire('Erro', erro.message, 'error');
            return;
        }
        $('#compra_idempotency_key').val(chaveCompra);
        compraEmCurso = true;
        $('#btnComprar').prop('disabled', true);
        Swal.fire({
            title: 'A processar pagamento',
            text: 'Aguarde a resposta do M-Pesa e confirme no telefone, caso seja solicitado.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });
        $.ajax({
            url: @json(route('compras.sms.comprar')),
            method: 'POST',
            headers: {
                'Idempotency-Key': chaveCompra,
                'Accept': 'application/json'
            },
            data: $('#formComprarSms').serialize(),
            success: function (response) {
                // Uma resposta HTTP 200 não prova que a transacção foi paga.
                const mensagem = response.message || 'Pedido registado. Consulte o estado no histórico.';
                Swal.fire({
                    icon: response.sucesso === true ? 'success' : 'info',
                    title: response.sucesso === true ? 'Pagamento confirmado' : 'Pagamento por confirmar',
                    text: mensagem
                }).then(() => window.location.reload());
            },
            error: function (xhr) {
                const mensagem = mensagemErro(xhr, 'Não foi possível confirmar o estado do pagamento.');
                Swal.fire({
                    icon: 'warning',
                    title: 'Pagamento não confirmado',
                    text: mensagem + ' Verifique o histórico antes de tentar novamente.'
                });
            },
            complete: function () {
                compraEmCurso = false;
                $('#btnComprar').prop('disabled', !calculoValido);
            }
        });
    });

    $('.btn-tentar-pagamento').on('click', function () {
        const id = String($(this).data('id'));
        $('#compra_id').val(id);
        $('#pagarCompraId').text(id);
        $('#pagarQuantidade').text(formatarNumero($(this).data('quantidade')));
        $('#pagarTotal').text(formatarDinheiro($(this).data('total')));
        $('#telefonePagamento').val('');
        // Chave local de rastreio; o endpoint pagar() actual não exige Idempotency-Key.
        if (!chavesPagamentos[id]) {
            try { chavesPagamentos[id] = novaChave(); }
            catch (erro) { Swal.fire('Erro', erro.message, 'error'); return; }
        }
        $('#pagamento_idempotency_key').val(chavesPagamentos[id]);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPagarCompra')).show();
    });

    $('#formPagarCompra').on('submit', function (e) {
        e.preventDefault();
        if (pagamentoEmCurso) return;
        const compraId = $('#compra_id').val();
        const telefone = $('#telefonePagamento').val().trim();
        if (!validarTelefone(telefone)) {
            Swal.fire('Telefone inválido', 'Introduza um número M-Pesa iniciado por 84 ou 85.', 'warning');
            return;
        }
        pagamentoEmCurso = true;
        $('#btnPagarCompra').prop('disabled', true);
        const url = @json(route('compras.sms.pagar', ':id')).replace(':id', encodeURIComponent(compraId));
        Swal.fire({
            title: 'A processar pagamento',
            text: 'Confirme a operação no telefone, caso seja solicitado.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: $('#formPagarCompra').serialize(),
            success: function (response) {
                Swal.fire({
                    icon: response.sucesso === true ? 'success' : 'info',
                    title: response.sucesso === true ? 'Pagamento confirmado' : 'Pagamento por confirmar',
                    text: response.message || 'Consulte o estado da compra no histórico.'
                }).then(() => window.location.reload());
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pagamento não confirmado',
                    text: mensagemErro(xhr, 'Não foi possível confirmar o pagamento.') +
                        ' Consulte o histórico antes de repetir.'
                });
            },
            complete: function () {
                pagamentoEmCurso = false;
                $('#btnPagarCompra').prop('disabled', false);
            }
        });
    });

    $('#modalComprarSms').on('hidden.bs.modal', function () {
        if (compraEmCurso) return;
        // Preservar a chave até o resultado ser conhecido; fechar o modal não cancela M-Pesa.
        if (chaveCompra) return;
        $('#formComprarSms')[0].reset();
        invalidarCalculo();
    });
});
</script>
@endsection
