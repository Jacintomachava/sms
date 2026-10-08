@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    {{-- =========================================================
         CARD / LISTAGEM
    ========================================================== --}}
    <div class="card title-line overflow-hidden member-wrapper">

        <div class="card-header card-no-border">

            <div class="header-top">

                <div>
                    <h4>Contas</h4>

                    <p class="f-m-light mt-1">
                        Gestão das contas da plataforma SMS.
                    </p>
                </div>

                <div>

                    <button
                        class="btn btn-primary"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCriarConta">

                        <i class="fa fa-plus me-1"></i>
                        Criar Conta

                    </button>

                </div>

            </div>

        </div>


        {{-- =====================================================
             TABELA
        ====================================================== --}}
        <div class="card-body">

            <div class="table-responsive">

                <table class="display" id="basic-1">

                    <thead>

                        <tr>
                            <th>Conta</th>
                            <th>Tipo</th>
                            <th>Cobrança</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Estado</th>
                            <th>Acções</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($contas as $conta)

                            <tr>

                                {{-- CONTA --}}
                                <td>

                                    <strong>
                                        {{ $conta->nome }}
                                    </strong>

                                    @if($conta->nome_legal)

                                        <br>

                                        <small class="text-muted">
                                            {{ $conta->nome_legal }}
                                        </small>

                                    @endif

                                </td>


                                {{-- TIPO --}}
                                <td>
                                    {{ $conta->tipo }}
                                </td>


                                {{-- COBRANÇA --}}
                                <td>

                                    @if($conta->tipo_cobranca === 'PRE_PAGO')

                                        <span class="badge badge-light-primary">
                                            Pré-pago
                                        </span>

                                    @elseif($conta->tipo_cobranca === 'POS_PAGO')

                                        <span class="badge badge-light-success">
                                            Pós-pago
                                        </span>

                                    @else

                                        <span class="badge badge-light-secondary">
                                            {{ $conta->tipo_cobranca }}
                                        </span>

                                    @endif

                                </td>


                                {{-- EMAIL --}}
                                <td>
                                    {{ $conta->email ?? '-' }}
                                </td>


                                {{-- TELEFONE --}}
                                <td>
                                    {{ $conta->telefone ?? '-' }}
                                </td>


                                {{-- ESTADO --}}
                                <td>

                                    @if($conta->estado === 'ACTIVA')

                                        <span class="badge badge-light-success">
                                            Activa
                                        </span>

                                    @elseif($conta->estado === 'SUSPENSA')

                                        <span class="badge badge-light-warning">
                                            Suspensa
                                        </span>

                                    @else

                                        <span class="badge badge-light-danger">
                                            Bloqueada
                                        </span>

                                    @endif

                                </td>


                                {{-- ACÇÕES --}}
                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light"
                                        title="Configurar">

                                        <i class="fa fa-cog"></i>

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



{{-- =============================================================
     MODAL CRIAR CONTA
============================================================= --}}
<div
    class="modal fade"
    id="modalCriarConta"
    tabindex="-1"
    aria-labelledby="modalCriarContaLabel"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">

            {{-- =================================================
                 HEADER
            ================================================== --}}
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalCriarContaLabel">

                    Criar Conta

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar">
                </button>

            </div>


            {{-- =================================================
                 FORM
            ================================================== --}}
            <form
                id="formCriarConta"
                novalidate>

                @csrf


                <div class="modal-body">


                    {{-- =========================================
                         ERROS
                    ========================================== --}}
                    <div
                        id="erroCriarConta"
                        class="alert alert-danger"
                        style="display:none;">
                    </div>



                    {{-- =========================================
                         TIPO DE CONTA / COBRANÇA
                    ========================================== --}}
                    <h6 class="mb-3">
                        Dados da conta
                    </h6>

                    <div class="row">

                        {{-- TIPO CONTA --}}
                        <div class="col-md-6 mb-3">

                            <label
                                class="form-label"
                                for="tipo_conta">

                                Tipo de conta

                            </label>

                            <select
                                class="form-select"
                                name="tipo_conta"
                                id="tipo_conta">

                                <option value="INDIVIDUAL">
                                    Individual
                                </option>

                                <option value="EMPRESA">
                                    Empresa
                                </option>

                            </select>

                        </div>


                        {{-- TIPO COBRANÇA --}}
                        <div class="col-md-6 mb-3">

                            <label
                                class="form-label"
                                for="tipo_cobranca">

                                Tipo de cobrança

                            </label>

                            <select
                                class="form-select"
                                name="tipo_cobranca"
                                id="tipo_cobranca">

                                <option value="PRE_PAGO">
                                    Pré-pago
                                </option>

                                <option value="POS_PAGO">
                                    Pós-pago
                                </option>

                            </select>

                        </div>

                    </div>



                    <hr>


                    {{-- =========================================
                         RESPONSÁVEL
                    ========================================== --}}
                    <h6 class="mb-3">
                        Responsável da conta
                    </h6>

                    <div class="row">

                        {{-- NOME --}}
                        <div class="col-md-12 mb-3">

                            <label
                                class="form-label"
                                for="name">

                                Nome completo

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="name"
                                id="name"
                                placeholder="Nome completo">

                        </div>


                        {{-- EMAIL --}}
                        <div class="col-md-6 mb-3">

                            <label
                                class="form-label"
                                for="email">

                                Email

                            </label>

                            <input
                                type="email"
                                class="form-control"
                                name="email"
                                id="email"
                                placeholder="exemplo@email.com">

                        </div>


                        {{-- TELEFONE --}}
                        <div class="col-md-6 mb-3">

                            <label
                                class="form-label"
                                for="telefone">

                                Telefone

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="telefone"
                                id="telefone"
                                placeholder="84xxxxxxx">

                        </div>


                        {{-- PASSWORD --}}
                        <div class="col-md-6 mb-3">

                            <label
                                class="form-label"
                                for="password">

                                Palavra-passe

                            </label>

                            <input
                                type="password"
                                class="form-control"
                                name="password"
                                id="password"
                                placeholder="Mínimo 8 caracteres">

                        </div>


                        {{-- CONFIRMAR PASSWORD --}}
                        <div class="col-md-6 mb-3">

                            <label
                                class="form-label"
                                for="password_confirmation">

                                Confirmar palavra-passe

                            </label>

                            <input
                                type="password"
                                class="form-control"
                                name="password_confirmation"
                                id="password_confirmation"
                                placeholder="Confirme a palavra-passe">

                        </div>

                    </div>



                    {{-- =========================================
                         DADOS DA EMPRESA
                    ========================================== --}}
                    <div
                        id="camposEmpresaAdmin"
                        style="display:none;">

                        <hr>

                        <h6 class="mb-3">
                            Dados da empresa
                        </h6>

                        <div class="row">

                            {{-- NOME EMPRESA --}}
                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label"
                                    for="nome_empresa">

                                    Nome da empresa

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="nome_empresa"
                                    id="nome_empresa"
                                    placeholder="Ex.: INFORDATA">

                            </div>


                            {{-- NOME LEGAL --}}
                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label"
                                    for="nome_legal">

                                    Nome legal

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="nome_legal"
                                    id="nome_legal"
                                    placeholder="Ex.: INFORDATA, LDA">

                            </div>


                            {{-- NUIT --}}
                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label"
                                    for="nuit">

                                    NUIT

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="nuit"
                                    id="nuit"
                                    placeholder="NUIT da empresa">

                            </div>

                        </div>

                    </div>



                    {{-- =========================================
                         CONFIGURAÇÃO POS-PAGO
                    ========================================== --}}
                    <div
                        id="camposPosPago"
                        style="display:none;">

                        <hr>

                        <h6 class="mb-1">
                            Configuração Pós-pago
                        </h6>

                        <p class="text-muted mb-3">
                            Defina a tarifa e o limite interno
                            autorizado pela INFORDATA.
                        </p>


                        <div class="row">

                            {{-- TARIFA --}}
                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label"
                                    for="tarifa_sms_id">

                                    Tarifa SMS

                                </label>

                                <select
                                    class="form-select"
                                    name="tarifa_sms_id"
                                    id="tarifa_sms_id">

                                    <option value="">
                                        Seleccione
                                    </option>

                                    @foreach($tarifas as $tarifa)

                                        <option
                                            value="{{ $tarifa->id }}">

                                            {{ $tarifa->nome }}
                                            -
                                            {{ number_format(
                                                $tarifa->preco_sms,
                                                4,
                                                ',',
                                                '.'
                                            ) }}
                                            MT

                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- LIMITE INTERNO --}}
                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label"
                                    for="limite_interno_sms">

                                    Limite interno INFORDATA

                                </label>

                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="form-control"
                                    name="limite_interno_sms"
                                    id="limite_interno_sms"
                                    placeholder="Ex.: 100000">

                                <small class="text-muted">

                                    Limite mensal interno.
                                    Não será apresentado ao cliente.

                                </small>

                            </div>

                        </div>



                        {{-- =====================================
                             CONFIGURAR LIMITE CLIENTE
                        ====================================== --}}
                        <div class="row">

                            <div class="col-md-12 mb-3">

                                <div class="form-check">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="configurar_limite_cliente">

                                    <label
                                        class="form-check-label"
                                        for="configurar_limite_cliente">

                                        Configurar limite mensal
                                        definido pelo cliente

                                    </label>

                                </div>

                            </div>

                        </div>



                        {{-- =====================================
                             CAMPOS LIMITE CLIENTE
                        ====================================== --}}
                        <div
                            id="camposLimiteCliente"
                            style="display:none;">

                            <div class="row">

                                {{-- LIMITE --}}
                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label"
                                        for="limite_cliente_sms">

                                        Limite mensal

                                    </label>

                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="form-control"
                                        name="limite_cliente_sms"
                                        id="limite_cliente_sms"
                                        placeholder="Ex.: 80000">

                                </div>


                                {{-- CORTE --}}
                                <div class="col-md-3 mb-3">

                                    <label
                                        class="form-label"
                                        for="corte_percentual">

                                        Corte (%)

                                    </label>

                                    <input
                                        type="number"
                                        min="100"
                                        step="1"
                                        class="form-control"
                                        name="corte_percentual"
                                        id="corte_percentual"
                                        value="100">

                                </div>


                                {{-- BLOQUEIO --}}
                                <div class="col-md-3 mb-3">

                                    <label class="form-label d-block">
                                        Bloqueio
                                    </label>

                                    <div class="form-check mt-2">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            value="1"
                                            name="bloqueio_activo"
                                            id="bloqueio_activo">

                                        <label
                                            class="form-check-label"
                                            for="bloqueio_activo">

                                            Activar corte

                                        </label>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                {{-- =============================================
                     FOOTER
                ============================================== --}}
                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnCriarConta">

                        <i class="fa fa-save me-1"></i>

                        <span id="textoBtnCriarConta">
                            Criar Conta
                        </span>

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
    | CSRF
    |--------------------------------------------------------------------------
    */
    $.ajaxSetup({

        headers: {

            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content')

        }

    });


    /*
    |--------------------------------------------------------------------------
    | MOSTRAR / ESCONDER CAMPOS
    |--------------------------------------------------------------------------
    */
    function actualizarCamposConta() {

        let tipoConta =
            $('#tipo_conta').val();

        let tipoCobranca =
            $('#tipo_cobranca').val();


        /*
        |--------------------------------------------------------------------------
        | EMPRESA
        |--------------------------------------------------------------------------
        */
        if (tipoConta === 'EMPRESA') {

            $('#camposEmpresaAdmin')
                .stop(true, true)
                .slideDown(180);

        } else {

            $('#camposEmpresaAdmin')
                .stop(true, true)
                .slideUp(180);

            /*
            | Limpar erros visuais.
            */
            $('#nome_empresa, #nome_legal, #nuit')
                .removeClass('error');

            $('#camposEmpresaAdmin label.error')
                .remove();
        }


        /*
        |--------------------------------------------------------------------------
        | POS-PAGO
        |--------------------------------------------------------------------------
        */
        if (tipoCobranca === 'POS_PAGO') {

            $('#camposPosPago')
                .stop(true, true)
                .slideDown(180);

        } else {

            $('#camposPosPago')
                .stop(true, true)
                .slideUp(180);

            /*
            | Limpar configuração POS quando voltar para PRE.
            */
            $('#tarifa_sms_id')
                .val('');

            $('#limite_interno_sms')
                .val('');

            $('#configurar_limite_cliente')
                .prop('checked', false);

            $('#camposLimiteCliente')
                .hide();

            $('#limite_cliente_sms')
                .val('');

            $('#corte_percentual')
                .val('100');

            $('#bloqueio_activo')
                .prop('checked', false);

            $('#camposPosPago label.error')
                .remove();

            $('#camposPosPago .error')
                .removeClass('error');
        }

    }


    /*
    |--------------------------------------------------------------------------
    | ALTERAR TIPO CONTA / COBRANÇA
    |--------------------------------------------------------------------------
    */
    $('#tipo_conta, #tipo_cobranca')
        .on(
            'change',
            function () {

                actualizarCamposConta();

            }
        );


    /*
    |--------------------------------------------------------------------------
    | LIMITE DO CLIENTE
    |--------------------------------------------------------------------------
    */
    $('#configurar_limite_cliente')
        .on(
            'change',
            function () {

                if ($(this).is(':checked')) {

                    $('#camposLimiteCliente')
                        .stop(true, true)
                        .slideDown(180);

                } else {

                    $('#camposLimiteCliente')
                        .stop(true, true)
                        .slideUp(180);

                    $('#limite_cliente_sms')
                        .val('')
                        .removeClass('error');

                    $('#corte_percentual')
                        .val('100')
                        .removeClass('error');

                    $('#bloqueio_activo')
                        .prop('checked', false);

                    $('#camposLimiteCliente label.error')
                        .remove();
                }

            }
        );


    /*
    |--------------------------------------------------------------------------
    | ESTADO INICIAL
    |--------------------------------------------------------------------------
    */
    actualizarCamposConta();


    /*
    |--------------------------------------------------------------------------
    | JQUERY VALIDATE
    |--------------------------------------------------------------------------
    */
    $('#formCriarConta').validate({

        /*
        |--------------------------------------------------------------------------
        | NÃO VALIDAR DURANTE DIGITAÇÃO/CLIQUE
        |--------------------------------------------------------------------------
        */
        onfocusout: false,
        onclick: false,
        onkeyup: false,


        /*
        |--------------------------------------------------------------------------
        | REGRAS
        |--------------------------------------------------------------------------
        */
        rules: {

            tipo_conta: {
                required: true
            },

            tipo_cobranca: {
                required: true
            },

            name: {
                required: true,
                minlength: 3,
                maxlength: 255
            },

            email: {
                required: true,
                email: true,
                maxlength: 255
            },

            telefone: {
                required: true,
                minlength: 9,
                maxlength: 20
            },

            password: {
                required: true,
                minlength: 8
            },

            password_confirmation: {
                required: true,
                equalTo: '#password'
            },


            /*
            |--------------------------------------------------------------------------
            | EMPRESA
            |--------------------------------------------------------------------------
            */
            nome_empresa: {

                required: function () {

                    return $('#tipo_conta').val() === 'EMPRESA';

                },

                maxlength: 255
            },

            nome_legal: {

                required: function () {

                    return $('#tipo_conta').val() === 'EMPRESA';

                },

                maxlength: 255
            },

            nuit: {

                required: function () {

                    return $('#tipo_conta').val() === 'EMPRESA';

                },

                maxlength: 20
            },


            /*
            |--------------------------------------------------------------------------
            | POS-PAGO
            |--------------------------------------------------------------------------
            */
            tarifa_sms_id: {

                required: function () {

                    return $('#tipo_cobranca').val() === 'POS_PAGO';

                }

            },

            limite_interno_sms: {

                required: function () {

                    return $('#tipo_cobranca').val() === 'POS_PAGO';

                },

                min: 1,

                digits: true
            },


            /*
            |--------------------------------------------------------------------------
            | LIMITE CLIENTE
            |--------------------------------------------------------------------------
            */
            limite_cliente_sms: {

                required: function () {

                    return (
                        $('#tipo_cobranca').val() === 'POS_PAGO' &&
                        $('#configurar_limite_cliente').is(':checked')
                    );

                },

                min: 1,

                digits: true
            },

            corte_percentual: {

                required: function () {

                    return (
                        $('#tipo_cobranca').val() === 'POS_PAGO' &&
                        $('#configurar_limite_cliente').is(':checked') &&
                        $('#bloqueio_activo').is(':checked')
                    );

                },

                min: 100,

                digits: true
            }

        },


        /*
        |--------------------------------------------------------------------------
        | SUBMIT
        |--------------------------------------------------------------------------
        */
        submitHandler: function (form) {

            $.ajax({

                type: 'POST',

                url: "{{ route('admin.contas.store') }}",

                data: $(form).serialize(),


                /*
                |--------------------------------------------------------------------------
                | BEFORE
                |--------------------------------------------------------------------------
                */
                beforeSend: function () {

                    $('#erroCriarConta')
                        .hide()
                        .html('');

                    $('#btnCriarConta')
                        .prop('disabled', true);

                    $('#textoBtnCriarConta')
                        .text('A criar...');

                },


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */
                success: function (response) {

                    if (response.status == 1) {

                        Swal.fire({

                            icon: 'success',

                            title: 'Conta criada!',

                            text:
                                response.message ??
                                'Conta criada com sucesso.',

                            timer: 2000,

                            timerProgressBar: true,

                            showConfirmButton: false

                        }).then(function () {

                            window.location.reload();

                        });

                        return;
                    }


                    restaurarBotao();


                    Swal.fire({

                        icon: 'error',

                        title: 'Erro',

                        text:
                            response.message ??
                            'Não foi possível criar a conta.'

                    });

                },


                /*
                |--------------------------------------------------------------------------
                | ERROR
                |--------------------------------------------------------------------------
                */
                error: function (xhr) {

                    restaurarBotao();


                    /*
                    |--------------------------------------------------------------------------
                    | ERROS DE VALIDAÇÃO LARAVEL
                    |--------------------------------------------------------------------------
                    */
                    if (
                        xhr.status === 422 &&
                        xhr.responseJSON &&
                        xhr.responseJSON.errors
                    ) {

                        let html = '';


                        $.each(
                            xhr.responseJSON.errors,
                            function (campo, mensagens) {

                                html +=
                                    '<div>' +
                                    mensagens[0] +
                                    '</div>';

                            }
                        );


                        $('#erroCriarConta')
                            .html(html)
                            .show();


                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | OUTROS ERROS
                    |--------------------------------------------------------------------------
                    */
                    let mensagem =
                        'Não foi possível criar a conta.';


                    if (
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

                }

            });

        }

    });


    /*
    |--------------------------------------------------------------------------
    | RESTAURAR BOTÃO
    |--------------------------------------------------------------------------
    */
    function restaurarBotao() {

        $('#btnCriarConta')
            .prop('disabled', false);

        $('#textoBtnCriarConta')
            .text('Criar Conta');

    }


    /*
    |--------------------------------------------------------------------------
    | LIMPAR MODAL QUANDO FECHAR
    |--------------------------------------------------------------------------
    */
    $('#modalCriarConta').on(
        'hidden.bs.modal',
        function () {

            let form =
                $('#formCriarConta');


            /*
            | Reset HTML.
            */
            form[0].reset();


            /*
            | Reset jQuery Validate.
            */
            if (form.data('validator')) {

                form.validate().resetForm();

            }


            form
                .find('.error')
                .removeClass('error');


            $('#erroCriarConta')
                .hide()
                .html('');


            $('#camposEmpresaAdmin')
                .hide();


            $('#camposPosPago')
                .hide();


            $('#camposLimiteCliente')
                .hide();


            $('#corte_percentual')
                .val('100');


            restaurarBotao();


            actualizarCamposConta();

        }
    );

});

</script>

@endsection