@extends('layouts.app')

@section('conteudo')

<div class="col-xl-12">

    <div class="card title-line overflow-hidden">

        <div class="card-header card-no-border">

            <div class="header-top">

                <div>

                    <h2>SMS</h2>

                    <p class="mb-0 text-muted">
                        Gestão de SMS.
                    </p>

                </div>

                <a
                    type="button"
                    class="btn btn-primary btn-nova-tarifa"
                    href="{{ route('sms.hitorico') }}"
                >
                    <i class="fa fa-plus"></i>
                    Nova SMS
                </a>

            </div>

        </div>

    </div>

</div>


<div class="col-sm-12">

    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <table
                    class="display"
                    id="basic-1"
                >

                    <thead>

                        <tr>
                            <th>Tipo</th>
                            <th>Tarifa</th>
                            <th>Cliente</th>
                            <th>Quantidade</th>
                            <th>Preço/SMS</th>
                            <th>Pública</th>
                            <th>Estado</th>
                            <th>Acção</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($tarifas as $tarifa)

                            <tr>

                                <td>

                                    @if($tarifa->conta_id)

                                        <span class="badge bg-warning">
                                            Exclusiva
                                        </span>

                                    @else

                                        <span class="badge bg-primary">
                                            Geral
                                        </span>

                                    @endif

                                </td>


                                <td>
                                    <strong>
                                        {{ $tarifa->nome }}
                                    </strong>
                                </td>


                                <td>

                                    @if($tarifa->conta)

                                        {{ $tarifa->conta->nome }}

                                    @else

                                        Todas as contas

                                    @endif

                                </td>


                                <td>

                                    {{ number_format(
                                        $tarifa->quantidade_minima,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                    -

                                    @if($tarifa->quantidade_maxima)

                                        {{ number_format(
                                            $tarifa->quantidade_maxima,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                    @else

                                        ∞

                                    @endif

                                </td>


                                <td>

                                    <strong>
                                        {{ number_format(
                                            $tarifa->preco_sms,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                        MT
                                    </strong>

                                </td>


                                <td>

                                    @if($tarifa->publica)

                                        <span class="badge bg-success">
                                            Sim
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Não
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if($tarifa->activo)

                                        <span class="badge bg-success">
                                            Activa
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Inactiva
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm btn-editar-tarifa"

                                        data-id="{{ $tarifa->id }}"
                                        data-conta="{{ $tarifa->conta_id }}"
                                        data-nome="{{ $tarifa->nome }}"
                                        data-min="{{ $tarifa->quantidade_minima }}"
                                        data-max="{{ $tarifa->quantidade_maxima }}"
                                        data-preco="{{ $tarifa->preco_sms }}"
                                        data-publica="{{ $tarifa->publica ? 1 : 0 }}"
                                        data-inicio="{{ optional($tarifa->data_inicio)->format('Y-m-d') }}"
                                        data-fim="{{ optional($tarifa->data_fim)->format('Y-m-d') }}"
                                        data-observacao="{{ $tarifa->observacao }}"

                                        title="Editar"
                                    >
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <button
                                        type="button"

                                        class="btn btn-sm
                                            {{ $tarifa->activo
                                                ? 'btn-danger'
                                                : 'btn-success'
                                            }}
                                            btn-estado-tarifa"

                                        data-id="{{ $tarifa->id }}"

                                        data-activo="{{ $tarifa->activo ? 1 : 0 }}"

                                        data-nome="{{ $tarifa->nome }}"

                                        title="{{ $tarifa->activo
                                            ? 'Inactivar'
                                            : 'Activar'
                                        }}"
                                    >

                                        @if($tarifa->activo)

                                            <i class="fa fa-ban"></i>

                                        @else

                                            <i class="fa fa-check"></i>

                                        @endif

                                    </button>

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
    id="modalTarifa"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form id="formTarifa">

                @csrf

                <input
                    type="hidden"
                    id="tarifa_id"
                >
                


                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalTarifa"
                    >
                        Nova Tarifa
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
                                Tipo
                            </label>

                            <select
                                id="tipo_tarifa"
                                class="form-select"
                            >

                                <option value="GERAL">
                                    Geral
                                </option>

                                <option value="EXCLUSIVA">
                                    Exclusiva
                                </option>

                            </select>

                        </div>


                        <div
                            class="col-md-6 mb-3"
                            id="campoConta"
                            style="display:none;"
                        >

                            <label class="form-label">
                                Cliente
                            </label>

                            <select
                                name="conta_id"
                                id="conta_id"
                                class="form-select"
                            >

                                <option value="">
                                    Selecione
                                </option>

                                @foreach($contas as $conta)

                                    <option value="{{ $conta->id }}">
                                        {{ $conta->nome }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Nome
                            </label>

                            <input
                                type="text"
                                name="nome"
                                id="nome_tarifa"
                                class="form-control"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Quantidade mínima
                            </label>

                            <input
                                type="number"
                                name="quantidade_minima"
                                id="quantidade_minima"
                                min="1"
                                class="form-control"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Quantidade máxima
                            </label>

                            <input
                                type="number"
                                name="quantidade_maxima"
                                id="quantidade_maxima"
                                min="1"
                                class="form-control"
                            >

                            <small class="text-muted">
                                Deixe vazio para não ter limite máximo.
                            </small>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Preço por SMS (MT)
                            </label>

                            <input
                                type="number"
                                name="preco_sms"
                                id="preco_sms"
                                step="0.0001"
                                min="0.0001"
                                class="form-control"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Visibilidade
                            </label>

                            <div class="form-check mt-2">

                                <input
                                    type="checkbox"
                                    name="publica"
                                    value="1"
                                    id="publica"
                                    class="form-check-input"
                                >

                                <label
                                    for="publica"
                                    class="form-check-label"
                                >
                                    Tarifa pública
                                </label>

                            </div>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Início da vigência
                            </label>

                            <input
                                type="date"
                                name="data_inicio"
                                id="data_inicio"
                                class="form-control"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Fim da vigência
                            </label>

                            <input
                                type="date"
                                name="data_fim"
                                id="data_fim"
                                class="form-control"
                            >

                        </div>


                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Observação
                            </label>

                            <textarea
                                name="observacao"
                                id="observacao"
                                class="form-control"
                                rows="3"
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
                        id="btnGuardarTarifa"
                        class="btn btn-primary"
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
    | TIPO DE TARIFA
    |--------------------------------------------------------------------------
    */
    $('#tipo_tarifa').on('change', function () {

        if ($(this).val() === 'EXCLUSIVA') {

            $('#campoConta').show();

            $('#conta_id').prop(
                'disabled',
                false
            );

            $('#publica')
                .prop('checked', false)
                .prop('disabled', true);

        } else {

            $('#campoConta').hide();

            $('#conta_id')
                .val('')
                .prop('disabled', true);

            $('#publica')
                .prop('disabled', false);

        }

    });


    /*
    |--------------------------------------------------------------------------
    | NOVA TARIFA
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '.btn-nova-tarifa',
        function () {

            $('#formTarifa')[0].reset();

            $('#tarifa_id').val('');

            $('#tituloModalTarifa')
                .text('Nova Tarifa');

            $('#tipo_tarifa')
                .val('GERAL')
                .trigger('change');

            /*
             * Geral será pública por padrão.
             */
            $('#publica')
                .prop('checked', true);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | EDITAR
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '.btn-editar-tarifa',
        function () {

            let botao = $(this);

            let contaId =
                botao.data('conta');


            $('#tarifa_id').val(
                botao.data('id')
            );

            $('#nome_tarifa').val(
                botao.data('nome')
            );

            $('#quantidade_minima').val(
                botao.data('min')
            );

            $('#quantidade_maxima').val(
                botao.data('max')
            );

            $('#preco_sms').val(
                botao.data('preco')
            );

            $('#data_inicio').val(
                botao.data('inicio')
            );

            $('#data_fim').val(
                botao.data('fim')
            );

            $('#observacao').val(
                botao.data('observacao')
            );


            if (contaId) {

                $('#tipo_tarifa')
                    .val('EXCLUSIVA')
                    .trigger('change');

                $('#conta_id').val(
                    contaId
                );

            } else {

                $('#tipo_tarifa')
                    .val('GERAL')
                    .trigger('change');

            }


            $('#publica').prop(
                'checked',
                botao.data('publica') == 1
            );


            $('#tituloModalTarifa')
                .text('Editar Tarifa');


            let modal =
                new bootstrap.Modal(
                    document.getElementById(
                        'modalTarifa'
                    )
                );

            modal.show();

        }
    );

    /*
    |--------------------------------------------------------------------------
    | GUARDAR / ACTUALIZAR TARIFA
    |--------------------------------------------------------------------------
    */

    $(document).on('submit', '#formTarifa', function (e) {

        e.preventDefault();
        e.stopImmediatePropagation();

        let form = this;
        let tarifaId = $('#tarifa_id').val();
        let tipoTarifa = $('#tipo_tarifa').val();

        /*
        * Validações simples no frontend.
        */
        if (!$('#nome_tarifa').val().trim()) {

            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe o nome da tarifa.'
            });

            return;
        }

        if (!$('#quantidade_minima').val()) {

            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe a quantidade mínima.'
            });

            return;
        }

        if (!$('#preco_sms').val()) {

            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe o preço por SMS.'
            });

            return;
        }

        /*
        * Tarifa exclusiva precisa de uma conta.
        */
        if (
            tipoTarifa === 'EXCLUSIVA' &&
            !$('#conta_id').val()
        ) {

            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Seleccione o cliente da tarifa exclusiva.'
            });

            return;
        }


        let formData = new FormData(form);

        /*
        * IMPORTANTE:
        * Quando é tarifa geral, conta_id deve ser NULL.
        */
        if (tipoTarifa === 'GERAL') {
            formData.delete('conta_id');
        }


        let url;
        let metodo;


        /*
        * NOVA TARIFA
        */
        if (!tarifaId) {

            url = "{{ route('admin.tarifas-sms.store') }}";

            metodo = 'POST';

        }

        /*
        * EDITAR TARIFA
        */
        else {

            url = "{{ url('/admin/tarifas-sms') }}/" + tarifaId;

            metodo = 'POST';

            /*
            * Laravel interpreta como PUT.
            */
            formData.append('_method', 'PUT');

        }


        let botao = $('#btnGuardarTarifa');

        let textoOriginal = botao.html();


        botao
            .prop('disabled', true)
            .html(
                '<i class="fa fa-spinner fa-spin"></i> A guardar...'
            );


        $.ajax({

            url: url,

            type: metodo,

            data: formData,

            processData: false,

            contentType: false,

            cache: false,

            success: function (response) {

                if (response.status == 1) {

                    /*
                    * Fechar modal
                    */
                    bootstrap.Modal
                        .getInstance(
                            document.getElementById('modalTarifa')
                        )
                        .hide();


                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: response.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(function () {

                        window.location.reload();

                    });

                }

            },


            error: function (xhr) {

                let mensagem =
                    'Não foi possível guardar a tarifa.';


                /*
                * Laravel ValidationException
                */
                if (
                    xhr.status === 422 &&
                    xhr.responseJSON &&
                    xhr.responseJSON.errors
                ) {

                    let errors =
                        xhr.responseJSON.errors;

                    let primeiraChave =
                        Object.keys(errors)[0];

                    if (
                        primeiraChave &&
                        errors[primeiraChave]
                    ) {

                        mensagem =
                            errors[primeiraChave][0];

                    }

                }

                /*
                * Outro erro com message
                */
                else if (
                    xhr.responseJSON &&
                    xhr.responseJSON.message
                ) {

                    mensagem =
                        xhr.responseJSON.message;

                }


                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: mensagem
                });

            },


            complete: function () {

                botao
                    .prop('disabled', false)
                    .html(textoOriginal);

            }

        });

    });

    /*
    |--------------------------------------------------------------------------
    | ACTIVAR / INACTIVAR TARIFA
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-estado-tarifa',
        function () {

            let botao = $(this);

            let id =
                botao.data('id');

            let activo =
                parseInt(botao.data('activo'));

            let nome =
                botao.data('nome');

            let acao =
                activo === 1
                    ? 'inactivar'
                    : 'activar';


            Swal.fire({

                icon: 'question',

                title:
                    activo === 1
                        ? 'Inactivar tarifa?'
                        : 'Activar tarifa?',

                html:
                    'Deseja realmente <strong>' +
                    acao +
                    '</strong> a tarifa:<br><br>' +
                    '<strong>' + nome + '</strong>?',

                showCancelButton: true,

                confirmButtonText:
                    activo === 1
                        ? 'Sim, inactivar'
                        : 'Sim, activar',

                cancelButtonText: 'Cancelar'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                let url =
                    "{{ url('/admin/tarifas-sms') }}"
                    + '/'
                    + id
                    + '/estado';


                botao.prop(
                    'disabled',
                    true
                );


                $.ajax({

                    url: url,

                    type: 'POST',

                    data: {

                        _token:
                            "{{ csrf_token() }}",

                        _method:
                            'PATCH'

                    },


                    success: function (response) {

                        if (response.status == 1) {

                            Swal.fire({

                                icon: 'success',

                                title: 'Sucesso',

                                text:
                                    response.message,

                                timer: 1600,

                                showConfirmButton:
                                    false

                            }).then(function () {

                                window.location.reload();

                            });

                        }

                    },


                    error: function (xhr) {

                        let mensagem =
                            'Não foi possível alterar o estado da tarifa.';


                        /*
                        * Erro de validação,
                        * por exemplo conflito ao reactivar.
                        */
                        if (
                            xhr.status === 422 &&
                            xhr.responseJSON &&
                            xhr.responseJSON.errors
                        ) {

                            let errors =
                                xhr.responseJSON.errors;

                            let primeiraChave =
                                Object.keys(errors)[0];

                            if (
                                primeiraChave &&
                                errors[primeiraChave]
                            ) {

                                mensagem =
                                    errors[primeiraChave][0];

                            }

                        }

                        else if (
                            xhr.responseJSON &&
                            xhr.responseJSON.message
                        ) {

                            mensagem =
                                xhr.responseJSON.message;

                        }


                        Swal.fire({

                            icon: 'error',

                            title: 'Erro',

                            text: mensagem

                        });

                    },


                    complete: function () {

                        botao.prop(
                            'disabled',
                            false
                        );

                    }

                });

            });

        }
    );

});

</script>

@endsection