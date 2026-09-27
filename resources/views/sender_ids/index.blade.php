@extends('layouts.app')

@section('conteudo')

<div class="col-xl-12 order-md-iii">

    <div class="card title-line overflow-hidden member-wrapper">

        <div class="card-header card-no-border">

            <div class="header-top">

                <h2>
                    <i class="fa fa-paper-plane m-r-10"></i>
                    Sender IDs
                </h2>

                <div class="card-header-right-icon">

                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#modalSender">

                        <i class="fa fa-plus"></i>
                        Novo Sender ID

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


<div class="col-sm-12" style="margin-top: -2%">

    <div class="card">

        <div class="card-header pb-0 card-no-border">

            <h4>Meus Sender IDs</h4>

            <span>
                Remetentes disponíveis ou solicitados pela sua conta.
            </span>

        </div>


        <div class="card-body">

            <div class="table-responsive custom-scrollbar">

                <table class="display" id="basic-1">

                    <thead>

                        <tr>

                            <th>Sender ID</th>

                            <th>Tipo</th>

                            <th>Estado</th>

                            <th>Descrição</th>

                            <th>Data</th>

                        </tr>

                    </thead>


                    <tbody>

                    @forelse($senders as $sender)

                        <tr>

                            {{-- SENDER --}}
                            <td>

                                <strong>
                                    {{ $sender->sender }}
                                </strong>

                            </td>


                            {{-- TIPO --}}
                            <td>

                                @if($sender->tipo === 'COMPARTILHADO')

                                    <span class="badge bg-info">
                                        Compartilhado
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        Exclusivo
                                    </span>

                                @endif

                            </td>


                            {{-- ESTADO --}}
                            <td>

                                @switch($sender->estado)

                                    @case('PENDENTE')

                                        <span class="badge bg-warning text-dark">
                                            Pendente
                                        </span>

                                        @break


                                    @case('EM_APROVACAO_OPERADORA')

                                        <span class="badge bg-info">
                                            Em aprovação
                                        </span>

                                        @break


                                    @case('APROVADO')

                                        <span class="badge bg-success">
                                            Aprovado
                                        </span>

                                        @break


                                    @case('REJEITADO')

                                        <span class="badge bg-danger">
                                            Rejeitado
                                        </span>

                                        @break


                                    @case('SUSPENSO')

                                        <span class="badge bg-dark">
                                            Suspenso
                                        </span>

                                        @break

                                @endswitch

                            </td>


                            {{-- DESCRIÇÃO --}}
                            <td>

                                {{ $sender->descricao ?? '-' }}

                            </td>


                            {{-- DATA --}}
                            <td>

                                {{ $sender->created_at->format('d/m/Y H:i') }}

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="text-center">

                                Nenhum Sender ID encontrado.

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- MODAL NOVO SENDER ID --}}
{{-- ========================================================= --}}

<div
    class="modal fade"
    id="modalSender"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formSender">

                @csrf


                <div class="modal-header">

                    <h5 class="modal-title">
                        Solicitar Sender ID
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">


                    <div class="mb-3">

                        <label
                            for="sender"
                            class="form-label">

                            Sender ID
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="sender"
                            name="sender"
                            maxlength="50"
                            placeholder="Ex: INFORDATA"
                            required>

                        <small class="text-muted">

                            Nome que será apresentado
                            como remetente do SMS.

                        </small>

                    </div>


                    <div class="mb-3">

                        <label
                            for="descricao"
                            class="form-label">

                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            id="descricao"
                            name="descricao"
                            rows="3"
                            maxlength="255"
                            placeholder="Ex: Envio de notificações aos clientes"></textarea>

                    </div>


                </div>


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
                        id="btnSalvarSender">

                        <i
                            id="iconSender"
                            class="fa fa-paper-plane">
                        </i>

                        <span id="textoSender">
                            Solicitar
                        </span>

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>

@endsection


@push('js')

<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | SUBMETER SENDER
    |--------------------------------------------------------------------------
    */

    $('#formSender').on('submit', function (e) {

        e.preventDefault();


        let botao =
            $('#btnSalvarSender');


        /*
        |--------------------------------------------------------------------------
        | BLOQUEAR BOTÃO
        |--------------------------------------------------------------------------
        */

        botao.prop(
            'disabled',
            true
        );


        $('#iconSender')
            .removeClass(
                'fa-paper-plane'
            )
            .addClass(
                'fa-spinner fa-spin'
            );


        $('#textoSender')
            .text(
                'A processar...'
            );


        /*
        |--------------------------------------------------------------------------
        | AJAX
        |--------------------------------------------------------------------------
        */

        $.ajax({

            url:
                "{{ route('sender-ids.store') }}",

            type:
                "POST",

            data:
                $('#formSender').serialize(),


            /*
            |--------------------------------------------------------------------------
            | SUCESSO
            |--------------------------------------------------------------------------
            */

            success: function (response) {

                if (response.status == 1) {

                    /*
                     * Fecha o modal.
                     */

                    let modalElemento =
                        document.getElementById(
                            'modalSender'
                        );


                    let modal =
                        bootstrap.Modal.getInstance(
                            modalElemento
                        );


                    if (modal) {
                        modal.hide();
                    }


                    /*
                     * SweetAlert
                     */

                    Swal.fire({

                        icon:
                            'success',

                        title:
                            'Pedido submetido!',

                        text:
                            response.message,

                        confirmButtonText:
                            'OK',

                        timer:
                            2000,

                        timerProgressBar:
                            true

                    }).then(function () {

                        window.location.reload();

                    });


                    return;

                }


                restaurarBotaoSender();


                Swal.fire({

                    icon:
                        'warning',

                    title:
                        'Atenção',

                    text:
                        response.message ??
                        'Não foi possível solicitar o Sender ID.'

                });

            },


            /*
            |--------------------------------------------------------------------------
            | ERRO
            |--------------------------------------------------------------------------
            */

            error: function (xhr) {

                restaurarBotaoSender();


                /*
                |--------------------------------------------------------------------------
                | ERRO DE VALIDAÇÃO
                |--------------------------------------------------------------------------
                */

                if (
                    xhr.status === 422 &&
                    xhr.responseJSON
                ) {

                    let mensagem =
                        xhr.responseJSON.message ??
                        'Verifique os dados informados.';


                    if (
                        xhr.responseJSON.errors
                    ) {

                        let erros = [];


                        $.each(

                            xhr.responseJSON.errors,

                            function (
                                campo,
                                mensagens
                            ) {

                                erros.push(
                                    mensagens[0]
                                );

                            }

                        );


                        mensagem =
                            erros.join('<br>');

                    }


                    Swal.fire({

                        icon:
                            'warning',

                        title:
                            'Verifique os dados',

                        html:
                            mensagem,

                        confirmButtonText:
                            'Corrigir'

                    });


                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | OUTRO ERRO
                |--------------------------------------------------------------------------
                */

                let mensagem =
                    'Ocorreu um erro ao solicitar o Sender ID.';


                if (
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
                        mensagem,

                    confirmButtonText:
                        'OK'

                });

            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | RESTAURAR BOTÃO
    |--------------------------------------------------------------------------
    */

    function restaurarBotaoSender() {

        $('#btnSalvarSender')
            .prop(
                'disabled',
                false
            );


        $('#iconSender')
            .removeClass(
                'fa-spinner fa-spin'
            )
            .addClass(
                'fa-paper-plane'
            );


        $('#textoSender')
            .text(
                'Solicitar'
            );

    }

});

</script>

@endpush