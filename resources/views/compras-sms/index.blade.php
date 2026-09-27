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


{{-- MODAL REPETIR PAGAMENTO --}}
<div class="modal fade" id="modalPagarCompra" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    <i class="fa fa-credit-card"></i>
                    Pagar Compra
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form
                id="formPagarCompra"
                method="POST"
            >

                @csrf

                <input
                    type="hidden"
                    name="compra_id"
                    id="compra_id"
                >


                <div class="modal-body">

                    <div class="border rounded p-3 mb-3">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Compra</span>

                            <strong>
                                #<span id="pagarCompraId"></span>
                            </strong>
                        </div>


                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Quantidade</span>

                            <strong>
                                <span id="pagarQuantidade"></span>
                                SMS
                            </strong>
                        </div>


                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total</span>

                            <strong class="text-primary">
                                <span id="pagarTotal"></span>
                                MT
                            </strong>
                        </div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Número M-Pesa
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="telefone"
                            id="telefonePagamento"
                            class="form-control"
                            maxlength="9"
                            pattern="(84|85)[0-9]{7}"
                            placeholder="84xxxxxxx ou 85xxxxxxx"
                            required
                        >

                        <small class="text-muted">
                            Introduza o número M-Pesa que irá efectuar o pagamento.
                        </small>

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
                        id="btnPagarCompra"
                    >
                        <i class="fa fa-credit-card"></i>
                        Pagar Agora
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection

@section('script')

<script>


$(document).ready(function () {

    let timerCalculo = null;
    let calculoValido = false;


    function formatarNumero(valor)
    {
        return new Intl.NumberFormat('pt-PT', {
            maximumFractionDigits: 0
        }).format(valor);
    }


    function formatarDinheiro(valor)
    {
        return new Intl.NumberFormat('pt-PT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(valor);
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULAR COMPRA
    |--------------------------------------------------------------------------
    */

    $('#quantidade_sms').on('input', function () {

        clearTimeout(timerCalculo);

        calculoValido = false;

        $('#btnComprar').prop('disabled', true);

        $('#resumoCompra').addClass('d-none');


        let quantidade = parseInt($(this).val());


        if (!quantidade || quantidade < 1) {
            return;
        }


        timerCalculo = setTimeout(function () {

            $.ajax({

                url: "{{ route('compras.sms.calcular') }}",

                type: "POST",

                data: {

                    _token: "{{ csrf_token() }}",

                    quantidade_sms: quantidade

                },


                success: function (response) {

                    if (response.status != 1) {
                        return;
                    }


                    let dados = response.data;


                    $('#resumoQuantidade').text(
                        formatarNumero(
                            dados.quantidade_sms
                        )
                    );


                    $('#resumoTarifa').text(
                        dados.tarifa
                    );


                    $('#resumoPreco').text(
                        formatarDinheiro(
                            dados.preco_unitario
                        )
                    );


                    $('#resumoTotal').text(
                        formatarDinheiro(
                            dados.valor_total
                        )
                    );


                    $('#resumoCompra')
                        .removeClass('d-none');


                    calculoValido = true;


                    $('#btnComprar')
                        .prop('disabled', false);

                },


                error: function (xhr) {

                    calculoValido = false;

                    $('#btnComprar')
                        .prop('disabled', true);


                    let mensagem =
                        xhr.responseJSON?.message
                        ?? 'Não foi possível calcular a tarifa.';


                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: mensagem
                    });

                }

            });

        }, 500);

    });


        /*
    |--------------------------------------------------------------------------
    | COMPRAR SMS
    |--------------------------------------------------------------------------
    */

    $('#formComprarSms').on('submit', function (e) {

        e.preventDefault();


        if (!calculoValido) {
            return;
        }


        let botao = $('#btnComprar');


        botao.prop('disabled', true);


        Swal.fire({

            title: 'A processar pagamento',

            html:
                'Foi enviado um pedido para o seu número M-Pesa.<br>' +
                '<strong>Confirme a transacção no telefone.</strong>',

            allowOutsideClick: false,

            allowEscapeKey: false,

            didOpen: () => {
                Swal.showLoading();
            }

        });


        $.ajax({

            url: "{{ route('compras.sms.comprar') }}",

            type: "POST",

            data: $('#formComprarSms').serialize(),


            success: function (response) {

                Swal.fire({

                    icon: 'success',

                    title: 'Pagamento efectuado',

                    text: response.message,

                    confirmButtonText: 'OK'

                }).then(function () {

                    window.location.reload();

                });

            },


            error: function (xhr) {

                botao.prop('disabled', false);


                let mensagem =
                    xhr.responseJSON?.message
                    ?? 'Não foi possível efectuar o pagamento.';


                Swal.fire({

                    icon: 'error',

                    title: 'Pagamento não concluído',

                    text: mensagem,

                    confirmButtonText: 'OK'

                }).then(function () {

                    /*
                     * A compra pode ter ficado PENDENTE.
                     * Recarregamos para mostrar no histórico.
                     */

                    window.location.reload();

                });

            }

        });

    });

        /*
    |--------------------------------------------------------------------------
    | ABRIR PAGAMENTO PENDENTE
    |--------------------------------------------------------------------------
    */

    $('.btn-tentar-pagamento').on('click', function () {

        let id = $(this).data('id');

        let quantidade =
            $(this).data('quantidade');

        let total =
            $(this).data('total');


        $('#compra_id').val(id);

        $('#pagarCompraId').text(id);


        $('#pagarQuantidade').text(
            formatarNumero(quantidade)
        );


        $('#pagarTotal').text(
            formatarDinheiro(total)
        );


        $('#telefonePagamento').val('');


        let modal = new bootstrap.Modal(
            document.getElementById(
                'modalPagarCompra'
            )
        );


        modal.show();

    });

        /*
    |--------------------------------------------------------------------------
    | PAGAR COMPRA PENDENTE
    |--------------------------------------------------------------------------
    */

    $('#formPagarCompra').on('submit', function (e) {

        e.preventDefault();


        let compraId =
            $('#compra_id').val();


        let telefone =
            $('#telefonePagamento').val();


        let botao =
            $('#btnPagarCompra');


        botao.prop('disabled', true);


        let url =
            "{{ route('compras.sms.pagar', ':id') }}";


        url = url.replace(
            ':id',
            compraId
        );


        Swal.fire({

            title: 'A processar pagamento',

            text: 'Confirme a transacção no seu telefone.',

            allowOutsideClick: false,

            allowEscapeKey: false,

            didOpen: () => {

                Swal.showLoading();

            }

        });


        $.ajax({

            url: url,

            type: "POST",

            data: {

                _token: "{{ csrf_token() }}",

                telefone: telefone

            },


            success: function (response) {

                Swal.fire({

                    icon: 'success',

                    title: 'Pagamento efectuado',

                    text: response.message,

                    confirmButtonText: 'OK'

                }).then(function () {

                    window.location.reload();

                });

            },


            error: function (xhr) {

                botao.prop('disabled', false);


                let mensagem =
                    xhr.responseJSON?.message
                    ?? 'Não foi possível efectuar o pagamento.';


                Swal.fire({

                    icon: 'error',

                    title: 'Pagamento não concluído',

                    text: mensagem

                });

            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | LIMPAR MODAL
    |--------------------------------------------------------------------------
    */

    $('#modalComprarSms').on('hidden.bs.modal', function () {

        $('#formComprarSms')[0].reset();

        $('#resumoCompra')
            .addClass('d-none');

        $('#btnComprar')
            .prop('disabled', true);

        calculoValido = false;

    });

});

</script>

@endsection

