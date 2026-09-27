@extends('layouts.app')

@section('conteudo')

<div class="col-xl-12 order-md-iii">

    <div class="card title-line overflow-hidden member-wrapper">

        <div class="card-header card-no-border">

            <div class="header-top">

                <div>
                    <h2>Contactos</h2>

                    <p class="mb-0 text-muted">
                        Faça a gestão dos contactos da sua conta.
                    </p>
                </div>


                <div>
                     
                    <a
                        href="{{ route('contactos.importacao.modelo') }}"
                        class="btn btn-light"
                    >
                        <i class="fa fa-download"></i>
                        Baixar Modelo
                    </a>

                    <button
                        type="button"
                        class="btn btn-success"
                        data-bs-toggle="modal"
                        data-bs-target="#modalImportarContactos"
                    >
                        <i class="fa fa-upload"></i>
                        Importar Contactos
                    </button>

                    <button
                        class="btn btn-primary"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalContacto"
                    >
                        <i class="fa fa-plus"></i>
                        Novo Contacto
                    </button>
                </div>

            </div>

        </div>

    </div>

</div>


<div class="col-sm-12" style="margin-top: -2%">

    <div class="card">

        <div class="card-body">

            <div class="table-responsive custom-scrollbar">

                <table class="display" id="basic-1">

                    <thead>

                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Data de Nascimento</th>
                            <th>Grupos</th>
                            <th>Estado</th>
                            <th>Acção</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($contactos as $contacto)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $contacto->nome }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $contacto->telefone }}
                                </td>

                                <td>
                                    {{ $contacto->email ?? '-' }}
                                </td>

                                <td>
                                    @if($contacto->data_nascimento)

                                        {{ $contacto->data_nascimento->format('d/m/Y') }}

                                    @else

                                        -

                                    @endif
                                </td>

                                <td>

                                    @forelse($contacto->grupos as $grupo)

                                        <span class="badge bg-light text-dark">
                                            {{ $grupo->nome }}
                                        </span>

                                    @empty

                                        <span class="text-muted">
                                            Sem grupo
                                        </span>

                                    @endforelse

                                </td>

                                <td>

                                    @if($contacto->estado === 'ACTIVO')

                                        <span class="badge bg-success">
                                            Activo
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Inactivo
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm btn-editar-contacto"

                                        data-id="{{ $contacto->id }}"
                                        data-nome="{{ $contacto->nome }}"
                                        data-telefone="{{ $contacto->telefone }}"
                                        data-email="{{ $contacto->email }}"
                                        data-nascimento="{{ optional($contacto->data_nascimento)->format('Y-m-d') }}"
                                        data-grupos='@json($contacto->grupos->pluck("id"))'
                                        title="Editar"
                                    >
                                        <i class="fa fa-edit"></i>
                                    </button>


                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm btn-estado-contacto"

                                        data-id="{{ $contacto->id }}"
                                        data-estado="{{ $contacto->estado }}"
                                        title="{{ $contacto->estado === 'ACTIVO' ? 'Inactivar' : 'Activar' }}"
                                    >

                                        @if($contacto->estado === 'ACTIVO')
                                            <i class="fa fa-ban"></i>
                                        @else
                                            <i class="fa fa-check"></i>
                                        @endif

                                    </button>


                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm btn-apagar-contacto"

                                        data-id="{{ $contacto->id }}"
                                        data-nome="{{ $contacto->nome }}"
                                        title="Apagar"
                                    >
                                        <i class="fa fa-trash"></i>
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



{{-- ============================================================
     MODAL NOVO CONTACTO
============================================================ --}}

<div
    class="modal fade"
    id="modalContacto"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form id="formContacto">

                @csrf

                <input type="hidden" id="contacto_id" value="">

                <div class="modal-header">

                    <h5 class="modal-title" id="tituloModalContacto">
                        Novo Contacto
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="row">


                        {{-- NOME --}}

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Nome
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="nome"
                                id="nome"
                                class="form-control"
                                placeholder="Nome do contacto"
                            >

                        </div>


                        {{-- TELEFONE --}}

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Telefone
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="telefone"
                                id="telefone"
                                class="form-control"
                                placeholder="Ex: 841234567"
                            >

                        </div>


                        {{-- EMAIL --}}

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                placeholder="email@exemplo.com"
                            >

                        </div>


                        {{-- DATA NASCIMENTO --}}

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Data de Nascimento
                            </label>

                            <input
                                type="date"
                                name="data_nascimento"
                                id="data_nascimento"
                                class="form-control"
                            >

                        </div>


                        {{-- GRUPOS --}}

                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Grupos
                            </label>

                            <select
                                name="grupos[]"
                                id="grupos"
                                class="form-select"
                                multiple
                            >

                                @foreach($grupos as $grupo)

                                    <option value="{{ $grupo->id }}">
                                        {{ $grupo->nome }}
                                    </option>

                                @endforeach

                            </select>

                            <small class="text-muted">
                                Pode seleccionar mais de um grupo.
                            </small>

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
                        id="btnGuardarContacto"
                    >
                        <i class="fa fa-save"></i>
                        Guardar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<div
    class="modal fade"
    id="modalImportarContactos"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog">

        <div class="modal-content">

            <form
                id="formImportarContactos"
                method="POST"
                action="{{ route('contactos.importacao.importar') }}"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Importar Contactos
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="alert alert-info">
                        Utilize o modelo disponibilizado pelo sistema.
                        A data de nascimento deve estar no formato
                        <strong>DD-MM-AAAA</strong>.
                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Ficheiro Excel
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="file"
                            name="ficheiro"
                            id="ficheiro"
                            class="form-control"
                            accept=".xlsx,.xls"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Grupo
                        </label>

                        <select
                            name="grupo_id"
                            id="grupo_importacao_id"
                            class="form-select"
                        >

                            <option value="">
                                Sem grupo
                            </option>

                            @foreach($grupos as $grupo)

                                <option value="{{ $grupo->id }}">
                                    {{ $grupo->nome }}
                                </option>

                            @endforeach

                        </select>

                        <small class="text-muted">
                            Opcional. Todos os contactos importados
                            serão associados ao grupo selecionado.
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
                        id="btnImportarContactos"
                    >
                        <i class="fa fa-upload"></i>
                        Importar
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

    $('#formContacto').validate({

        rules: {

            nome: {
                required: true,
                maxlength: 150
            },

            telefone: {
                required: true,
                maxlength: 20
            },

            email: {
                email: true,
                maxlength: 150
            }

        },

        submitHandler: function (form) {

            let botao = $('#btnGuardarContacto');

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR SE É REGISTO OU EDIÇÃO
            |--------------------------------------------------------------------------
            */
            let contactoId = $('#contacto_id').val();

            let url;
            let dados = $(form).serialize();


            if (contactoId) {

                /*
                 * EDITAR
                 *
                 * PUT /contactos/{id}
                 */
                url = "{{ url('/contactos') }}/" + contactoId;

                dados += '&_method=PUT';

            } else {

                /*
                 * NOVO CONTACTO
                 *
                 * POST /contactos
                 */
                url = "{{ route('contactos.store') }}";

            }

            /*
            |--------------------------------------------------------------------------
            | AJAX
            |--------------------------------------------------------------------------
            */
            $.ajax({

                url: url,
                type: "POST",
                data: dados,

                beforeSend: function () {

                    botao.prop('disabled', true);

                    botao.html(
                        '<i class="fa fa-spinner fa-spin"></i> A guardar...'
                    );

                },


                success: function (response) {

                    if (response.status == 1) {

                        $('#modalContacto').modal('hide');

                        Swal.fire({

                            icon: 'success',
                            title: 'Sucesso',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false

                        }).then(function () {

                            window.location.reload();

                        });

                    }

                },


                error: function (xhr) {

                    let mensagem =
                        'Não foi possível guardar o contacto.';

                    if (xhr.responseJSON && xhr.responseJSON.errors) {

                        let errors = xhr.responseJSON.errors;

                        mensagem = Object.values(errors)[0][0];

                    }
                    else if (xhr.responseJSON && xhr.responseJSON.message) {

                        mensagem = xhr.responseJSON.message;

                    }

                    Swal.fire({

                        icon: 'error',
                        title: 'Erro',
                        text: mensagem

                    });

                },


                complete: function () {

                    botao.prop('disabled', false);

                    botao.html(
                        '<i class="fa fa-save"></i> Guardar'
                    );

                }

            });

        }

    });

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | IMPORTAR CONTACTOS VIA AJAX
    |--------------------------------------------------------------------------
    */

    $('#formImportarContactos').on('submit', function (e) {

        // Impedir submit normal
        e.preventDefault();

        let form = this;
        let $form = $(this);

        // Validar formulário
        if (!$form.valid()) {
            return false;
        }

        let botao = $('#btnImportarContactos');

        // Necessário para enviar ficheiros
        let dados = new FormData(form);


        $.ajax({

            url: $form.attr('action'),

            method: 'POST',

            data: dados,

            processData: false,

            contentType: false,

            cache: false,

            beforeSend: function () {

                botao.prop('disabled', true);

                botao.html(
                    '<i class="fa fa-spinner fa-spin"></i> A importar...'
                );

            },

            success: function (response) {

                console.log('Resposta:', response);

                if (response.status == 1) {

                    let resultado =
                        'Total processado: <b>' +
                        response.total +
                        '</b><br>' +

                        'Importados: <b>' +
                        response.importados +
                        '</b><br>' +

                        'Duplicados: <b>' +
                        response.duplicados +
                        '</b><br>' +

                        'Inválidos: <b>' +
                        response.invalidos +
                        '</b>';


                    /*
                    |--------------------------------------------------------------------------
                    | MOSTRAR LINHAS INVÁLIDAS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        response.erros &&
                        response.erros.length > 0
                    ) {

                        resultado +=
                            '<hr>' +
                            '<div class="text-start">' +
                            '<strong>Linhas com erro:</strong><br>';


                        response.erros
                            .slice(0, 10)
                            .forEach(function (erro) {

                                resultado +=
                                    'Linha ' +
                                    erro.linha +
                                    ': ' +
                                    erro.erro +
                                    '<br>';

                            });


                        if (response.erros.length > 10) {

                            resultado +=
                                '<br>... e mais ' +
                                (response.erros.length - 10) +
                                ' erro(s).';

                        }

                        resultado += '</div>';

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | FECHAR MODAL
                    |--------------------------------------------------------------------------
                    */

                    let modalElemento =
                        document.getElementById(
                            'modalImportarContactos'
                        );

                    let modal =
                        bootstrap.Modal.getInstance(
                            modalElemento
                        );

                    if (modal) {
                        modal.hide();
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | RESULTADO
                    |--------------------------------------------------------------------------
                    */

                    Swal.fire({

                        icon:
                            response.invalidos > 0
                                ? 'warning'
                                : 'success',

                        title:
                            'Importação concluída',

                        html:
                            resultado,

                        confirmButtonText:
                            'OK'

                    }).then(function () {

                        window.location.reload();

                    });

                }
                else {

                    Swal.fire({

                        icon: 'error',

                        title: 'Erro',

                        text:
                            response.message ??
                            'Não foi possível importar os contactos.'

                    });

                }

            },

            error: function (xhr) {

                console.log(
                    'Erro:',
                    xhr.responseText
                );

                let mensagem =
                    'Não foi possível importar os contactos.';


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

                botao.html(
                    '<i class="fa fa-upload"></i> Importar'
                );

            }

        });


        return false;

    });

});


});



//Abir modal com campos activos para editar
$(document).on(
    'click',
    '.btn-editar-contacto',
    function () {

        let botao = $(this);

        $('#contacto_id').val(
            botao.data('id')
        );

        $('#nome').val(
            botao.data('nome')
        );

        $('#telefone').val(
            botao.data('telefone')
        );

        $('#email').val(
            botao.data('email')
        );

        $('#data_nascimento').val(
            botao.data('nascimento')
        );

        let grupos = botao.data('grupos');

        $('#grupos')
            .val(grupos)
            .trigger('change');

        $('#tituloModalContacto')
            .text('Editar Contacto');

        $('#modalContacto').modal('show');
    }
);

// Limpar Campos quando clica no novo
$(document).on(
    'click',
    '[data-bs-target="#modalContacto"]',
    function () {

        $('#formContacto')[0].reset();

        $('#contacto_id').val('');

        $('#grupos')
            .val([])
            .trigger('change');

        $('#tituloModalContacto')
            .text('Novo Contacto');

        $('#formContacto')
            .validate()
            .resetForm();
    }
);

//Activar INATIVAR CONTACTOS
$(document).on(
    'click',
    '.btn-estado-contacto',
    function () {

        let id = $(this).data('id');

        let estado = $(this).data('estado');

        let accao =
            estado === 'ACTIVO'
                ? 'inactivar'
                : 'activar';


        Swal.fire({

            title: 'Confirmar',

            text:
                'Deseja ' +
                accao +
                ' este contacto?',

            icon: 'question',

            showCancelButton: true,

            confirmButtonText: 'Sim',

            cancelButtonText: 'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            $.ajax({

                url:
                    "{{ url('/contactos') }}/" +
                    id +
                    "/estado",

                type: 'POST',

                data: {
                    _token:
                        "{{ csrf_token() }}",

                    _method: 'PATCH'
                },

                success: function (response) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false

                    }).then(function () {

                        window.location.reload();

                    });

                }

            });

        });

    }
);

//Apagar Contactos
$(document).on(
    'click',
    '.btn-apagar-contacto',
    function () {

        let id = $(this).data('id');
        let nome = $(this).data('nome');

        Swal.fire({

            title: 'Apagar contacto?',
            text: 'Deseja remover o contacto "' + nome +'"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, apagar',
            cancelButtonText: 'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }

            $.ajax({

                url: "{{ url('/contactos') }}/" + id,
                type: 'POST',

                data: {
                    _token: "{{ csrf_token() }}",
                    _method: 'DELETE'
                },

                success: function (response) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Removido',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false

                    }).then(function () {

                        window.location.reload();

                    });

                }

            });

        });

    }
);


</script>

@endsection