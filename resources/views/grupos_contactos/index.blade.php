@extends('layouts.app')

@section('conteudo')

<div class="col-xl-12 order-md-iii">

    <div class="card title-line overflow-hidden member-wrapper">

        <div class="card-header card-no-border">

            <div class="header-top">

                <div>

                    <h2>Grupos de Contactos</h2>

                    <p class="mb-0 text-muted">
                        Organize os seus contactos em grupos para
                        facilitar o envio de campanhas.
                    </p>

                </div>

                <div>

                    <button
                        class="btn btn-primary btn-novo-grupo"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalGrupo"
                    >
                        <i class="fa fa-plus"></i>
                        Novo Grupo
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

                <table
                    class="display"
                    id="basic-1"
                >

                    <thead>

                        <tr>
                            <th>Grupo</th>
                            <th>Descrição</th>
                            <th>Contactos</th>
                            <th>Estado</th>
                            <th>Data</th>
                            <th>Acção</th>
                        </tr>

                    </thead>


                    <tbody>

                        @foreach($grupos as $grupo)

                            <tr>

                                <td>

                                    <strong>
                                        {{ $grupo->nome }}
                                    </strong>

                                </td>


                                <td>

                                    {{ $grupo->descricao ?? '-' }}

                                </td>


                                <td>

                                    <span class="badge bg-primary">

                                        {{ $grupo->contactos_count }}

                                        {{ $grupo->contactos_count == 1
                                            ? 'contacto'
                                            : 'contactos'
                                        }}

                                    </span>

                                </td>


                                <td>

                                    @if($grupo->estado === 'ACTIVO')

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

                                    {{ $grupo->created_at->format('d/m/Y') }}

                                </td>


                                <td>

                                {{-- EDITAR --}}
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm btn-editar-grupo"
                                    data-id="{{ $grupo->id }}"
                                    data-nome="{{ $grupo->nome }}"
                                    data-descricao="{{ $grupo->descricao }}"
                                    title="Editar"
                                >
                                    <i class="fa fa-edit"></i>
                                </button>


                                {{-- ACTIVAR / INACTIVAR --}}
                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm btn-estado-grupo"
                                    data-id="{{ $grupo->id }}"
                                    data-nome="{{ $grupo->nome }}"
                                    data-estado="{{ $grupo->estado }}"
                                    title="{{ $grupo->estado === 'ACTIVO' ? 'Inactivar' : 'Activar' }}"
                                >

                                    @if($grupo->estado === 'ACTIVO')

                                        <i class="fa fa-ban"></i>

                                    @else

                                        <i class="fa fa-check"></i>

                                    @endif

                                </button>


                                {{-- APAGAR --}}
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm btn-apagar-grupo"
                                    data-id="{{ $grupo->id }}"
                                    data-nome="{{ $grupo->nome }}"
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
     MODAL NOVO GRUPO
============================================================ --}}

<div
    class="modal fade"
    id="modalGrupo"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formGrupo">

                @csrf

                <input type="hidden" id="grupo_id" name="grupo_id">

                <div class="modal-header">

                    <h5 class="modal-title" id="tituloModalGrupo">
                        Novo Grupo
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <div class="mb-3">

                        <label class="form-label">
                            Nome
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="nome"
                            id="nome"
                            class="form-control"
                            placeholder="Ex: Clientes Maputo"
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Descrição
                        </label>

                        <textarea
                            name="descricao"
                            class="form-control"
                            id="descricao"
                            rows="3"
                            placeholder="Descrição do grupo"
                        ></textarea>

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
                        id="btnGuardarGrupo"
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

    $('#formGrupo').validate({

        rules: {

            nome: {
                required: true,
                maxlength: 150
            },

            descricao: {
                maxlength: 255
            }

        },


        submitHandler: function (form) {

            let botao = $('#btnGuardarGrupo');

            // Verificar se é novo ou edição
            let grupoId = $('#grupo_id').val();

            let url;
            let dados = $(form).serialize();


            if (grupoId) {

                // EDITAR
                url = "{{ url('/grupos-contactos') }}/" + grupoId;

                // Laravel vai interpretar como PUT
                dados += '&_method=PUT';

            } else {

                // NOVO
                url = "{{ route('grupos-contactos.store') }}";

            }


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

                        $('#modalGrupo').modal('hide');

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
                        'Não foi possível guardar o grupo.';


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

                    botao.prop('disabled', false);

                    botao.html(
                        '<i class="fa fa-save"></i> Guardar'
                    );

                }

            });

        }

    });


    /*
    |--------------------------------------------------------------------------
    | NOVO GRUPO
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.btn-novo-grupo', function () {

        // Limpar formulário
        $('#formGrupo')[0].reset();

        // Sem ID significa NOVO
        $('#grupo_id').val('');

        // Título
        $('#tituloModalGrupo').text('Novo Grupo');

        // Limpar erros do jQuery Validate
        $('#formGrupo').validate().resetForm();

    });


    /*
    |--------------------------------------------------------------------------
    | EDITAR GRUPO
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.btn-editar-grupo', function () {

        let botao = $(this);

        // Colocar ID
        $('#grupo_id').val(
            botao.data('id')
        );

        // Preencher nome
        $('#nome').val(
            botao.data('nome')
        );

        // Preencher descrição
        $('#descricao').val(
            botao.data('descricao')
        );

        // Título
        $('#tituloModalGrupo').text('Editar Grupo');

        // Limpar mensagens antigas de validação
        $('#formGrupo').validate().resetForm();

        // Abrir modal
        $('#modalGrupo').modal('show');

    });

});


/*
|--------------------------------------------------------------------------
| NOVO GRUPO
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.btn-novo-grupo',
    function () {

        /*
         * Limpar formulário
         */

        $('#formGrupo')[0].reset();


        /*
         * Vazio significa NOVO
         */

        $('#grupo_id').val('');


        /*
         * Alterar título
         */

        $('#tituloModalGrupo')
            .text('Novo Grupo');


        /*
         * Limpar erros do jQuery Validate
         */

        $('#formGrupo')
            .validate()
            .resetForm();

    }
);

/*
|--------------------------------------------------------------------------
| ACTIVAR / INACTIVAR
|--------------------------------------------------------------------------
*/
$(document).on(
    'click',
    '.btn-estado-grupo',
    function () {

        let botao = $(this);
        let id = botao.data('id');
        let nome = botao.data('nome');
        let estado = botao.data('estado');

        let accao = estado === 'ACTIVO' ? 'inactivar' : 'activar';

        Swal.fire({

            title:
                estado === 'ACTIVO'
                    ? 'Inactivar grupo?'
                    : 'Activar grupo?',

            text:
                'Deseja ' +
                accao +
                ' o grupo "' +
                nome +
                '"?',

            icon: 'question',

            showCancelButton: true,

            confirmButtonText:
                estado === 'ACTIVO'
                    ? 'Sim, inactivar'
                    : 'Sim, activar',

            cancelButtonText:
                'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            $.ajax({

                url:
                    "{{ url('/grupos-contactos') }}/"
                    + id
                    + "/estado",

                type: "POST",

                data: {

                    _token:
                        "{{ csrf_token() }}",

                    _method:
                        "PATCH"

                },


                success: function (response) {

                    if (response.status == 1) {

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

                },


                error: function (xhr) {

                    Swal.fire({

                        icon: 'error',

                        title: 'Erro',

                        text:
                            xhr.responseJSON?.message
                            ??
                            'Não foi possível alterar o estado do grupo.'

                    });

                }

            });

        });

    }
);

/*
|--------------------------------------------------------------------------
| APAGAR GRUPO
|--------------------------------------------------------------------------
*/
$(document).on(
    'click',
    '.btn-apagar-grupo',
    function () {

        let botao = $(this);

        let id =
            botao.data('id');

        let nome =
            botao.data('nome');


        Swal.fire({

            title: 'Apagar grupo?',

            text:
                'Deseja apagar o grupo "' +
                nome +
                '"? Os contactos não serão apagados.',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonText:
                'Sim, apagar',

            cancelButtonText:
                'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            $.ajax({

                url:
                    "{{ url('/grupos-contactos') }}/"
                    + id,

                type: "POST",

                data: {

                    _token:
                        "{{ csrf_token() }}",

                    _method:
                        "DELETE"

                },


                success: function (response) {

                    if (response.status == 1) {

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

                },


                error: function (xhr) {

                    Swal.fire({

                        icon: 'error',

                        title: 'Erro',

                        text:
                            xhr.responseJSON?.message
                            ??
                            'Não foi possível apagar o grupo.'

                    });

                }

            });

        });

    }
);

</script>

@endsection