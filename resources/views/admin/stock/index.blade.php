@extends('layouts.app')

@section('conteudo')

<div class="col-xl-12 order-md-iii">

    <div class="card title-line overflow-hidden member-wrapper">

        <div class="card-header card-no-border">

            <div class="header-top">

                <div>
                    <h2>Stock de SMS</h2>

                    <p class="mb-0 text-muted">
                        Gestão das aquisições de SMS efectuadas
                        pela INFORDATA à Movitel.
                    </p>
                </div>

                <div>
                    <button
                        class="btn btn-primary btn-nova-compra"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCompra"
                    >
                        <i class="fa fa-plus"></i>
                        Nova Compra
                    </button>
                </div>

            </div>

        </div>

    </div>

</div>

<div class="row">

    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">

                <h6 class="mb-2">
                    Stock Disponível
                </h6>

                <h3>
                    {{ number_format(
                        $stockDisponivel,
                        0,
                        ',',
                        '.'
                    ) }}
                </h3>

                <span class="text-muted">
                    SMS disponíveis
                </span>

            </div>
        </div>
    </div>


    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">

                <h6 class="mb-2">
                    Total Adquirido
                </h6>

                <h3>
                    {{ number_format(
                        $totalAdquirido,
                        0,
                        ',',
                        '.'
                    ) }}
                </h3>

                <span class="text-muted">
                    SMS adquiridos
                </span>

            </div>
        </div>
    </div>


    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">

                <h6 class="mb-2">
                    Investimento
                </h6>

                <h3>
                    {{ number_format(
                        $valorInvestido,
                        2,
                        ',',
                        '.'
                    ) }}
                    MT
                </h3>

                <span class="text-muted">
                    Valor investido em stock
                </span>

            </div>
        </div>
    </div>

</div>

<div class="col-sm-12">

    <div class="card">

        <div class="card-body">

            <div class="table-responsive custom-scrollbar">

                <table
                    class="display"
                    id="basic-1"
                >

                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Referência</th>
                            <th>Quantidade</th>
                            <th>Disponível</th>
                            <th>Preço</th>
                            <th>Valor</th>
                            <th>Estado</th>
                            <th>Registado por</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($compras as $compra)

                            <tr>

                                <td>
                                    {{ $compra->data_compra->format('d/m/Y') }}
                                </td>

                                <td>
                                    {{ $compra->referencia ?? '-' }}
                                </td>

                                <td>
                                    {{ number_format(
                                        $compra->quantidade_sms,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format(
                                            $compra->quantidade_disponivel,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $compra->preco_unitario,
                                        4,
                                        ',',
                                        '.'
                                    ) }}
                                    MT
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $compra->valor_total,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                    MT
                                </td>

                                <td>

                                    @if($compra->estado === 'ACTIVO')

                                        <span class="badge bg-success">
                                            Activo
                                        </span>

                                    @elseif($compra->estado === 'ESGOTADO')

                                        <span class="badge bg-secondary">
                                            Esgotado
                                        </span>

                                    @else

                                        <span class="badge bg-danger">
                                            Cancelado
                                        </span>

                                    @endif

                                </td>

                                <td>
                                    {{ $compra->criadoPor?->name ?? '-' }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<div
    class="modal fade"
    id="modalCompra"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form id="formCompraStock">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Nova Compra de Stock
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">

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
                                placeholder="Ex: 200000"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Preço por SMS (MT)
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                name="preco_unitario"
                                id="preco_unitario"
                                class="form-control"
                                min="0.0001"
                                step="0.0001"
                                placeholder="Ex: 0.3000"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Data da Compra
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="date"
                                name="data_compra"
                                id="data_compra"
                                class="form-control"
                                value="{{ now()->format('Y-m-d') }}"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Referência / Factura
                            </label>

                            <input
                                type="text"
                                name="referencia"
                                id="referencia"
                                class="form-control"
                                maxlength="100"
                                placeholder="Referência da compra"
                            >

                        </div>


                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Valor Total
                            </label>

                            <input
                                type="text"
                                id="valor_total_visual"
                                class="form-control"
                                value="0,00 MT"
                                readonly
                            >

                        </div>


                        <div class="col-md-12">

                            <label class="form-label">
                                Observação
                            </label>

                            <textarea
                                name="observacao"
                                id="observacao"
                                class="form-control"
                                rows="3"
                                maxlength="1000"
                                placeholder="Observação opcional"
                            ></textarea>

                        </div>

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
                        id="btnGuardarCompra"
                    >
                        <i class="fa fa-save"></i>
                        Guardar
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

    /*
    |--------------------------------------------------------------------------
    | VALOR TOTAL
    |--------------------------------------------------------------------------
    */

    function calcularTotal() {

        let quantidade =
            parseInt($('#quantidade_sms').val()) || 0;

        let preco =
            parseFloat($('#preco_unitario').val()) || 0;

        let total = quantidade * preco;

        $('#valor_total_visual').val(
            total.toLocaleString(
                'pt-MZ',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ) + ' MT'
        );
    }


    $('#quantidade_sms, #preco_unitario')
        .on('input', function () {

            calcularTotal();

        });


    /*
    |--------------------------------------------------------------------------
    | NOVA COMPRA
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-nova-compra',
        function () {

            $('#formCompraStock')[0].reset();

            $('#data_compra').val(
                "{{ now()->format('Y-m-d') }}"
            );

            $('#valor_total_visual')
                .val('0,00 MT');

            $('#formCompraStock')
                .validate()
                .resetForm();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | GUARDAR
    |--------------------------------------------------------------------------
    */

    $('#formCompraStock').validate({

        rules: {

            quantidade_sms: {
                required: true,
                digits: true,
                min: 1
            },

            preco_unitario: {
                required: true,
                number: true,
                min: 0.0001
            },

            data_compra: {
                required: true
            },

            referencia: {
                maxlength: 100
            },

            observacao: {
                maxlength: 1000
            }
        },


        submitHandler: function (form) {

            let botao =
                $('#btnGuardarCompra');

            $.ajax({

                url:
                    "{{ route('admin.stock.store') }}",

                type:
                    "POST",

                data:
                    $(form).serialize(),


                beforeSend: function () {

                    botao.prop(
                        'disabled',
                        true
                    );

                    botao.html(
                        '<i class="fa fa-spinner fa-spin"></i> A guardar...'
                    );
                },


                success: function (response) {

                    if (response.status == 1) {

                        $('#modalCompra')
                            .modal('hide');

                        Swal.fire({

                            icon:
                                'success',

                            title:
                                'Sucesso',

                            text:
                                response.message,

                            timer:
                                2000,

                            showConfirmButton:
                                false

                        }).then(function () {

                            window.location.reload();

                        });

                    }
                },


                error: function (xhr) {

                    let mensagem =
                        'Não foi possível registar a compra.';

                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.errors
                    ) {

                        let errors =
                            xhr.responseJSON.errors;

                        mensagem =
                            Object.values(errors)[0][0];

                    }
                    else if (
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {

                        mensagem =
                            xhr.responseJSON.message;

                    }


                    Swal.fire({

                        icon:
                            'error',

                        title:
                            'Erro',

                        text:
                            mensagem
                    });
                },


                complete: function () {

                    botao.prop(
                        'disabled',
                        false
                    );

                    botao.html(
                        '<i class="fa fa-save"></i> Guardar'
                    );
                }

            });

        }

    });

});

</script>

@endsection