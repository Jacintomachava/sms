@extends('layouts.app')

@section('conteudo')

<div class="col-xl-12 order-md-iii">

    <div class="card title-line overflow-hidden member-wrapper">

        <div class="card-header card-no-border">

            <div class="header-top">

                <div>

                    <h2>
                        Gestão de Sender IDs
                    </h2>

                    <p class="mb-0 text-muted">
                        Gestão das solicitações de Sender ID
                        dos clientes da plataforma.
                    </p>

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

                            <th>Sender ID</th>

                            <th>Cliente</th>

                            <th>Tipo</th>

                            <th>Estado</th>

                            <th>Operadora</th>

                            <th>Data</th>

                            <th>Acção</th>

                        </tr>

                    </thead>


                    <tbody>

                    @foreach($senders as $sender)

                        <tr>

                            {{-- SENDER --}}

                            <td>

                                <strong>
                                    {{ $sender->sender }}
                                </strong>

                                @if($sender->descricao)

                                    <br>

                                    <small class="text-muted">
                                        {{ $sender->descricao }}
                                    </small>

                                @endif

                            </td>


                            {{-- CLIENTES --}}

                            <td>

                                @forelse($sender->contas as $conta)

                                    <span class="badge bg-light text-dark">
                                        {{ $conta->nome }}
                                    </span>

                                @empty

                                    <span class="text-muted">
                                        Sem conta
                                    </span>

                                @endforelse

                            </td>


                            {{-- TIPO --}}

                            <td>

                                @if(
                                    $sender->tipo ===
                                    'COMPARTILHADO'
                                )

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
                                            Na operadora
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


                            {{-- OPERADORA --}}

                            <td>

                                {{ $sender->operadora ?? '-' }}

                            </td>


                            {{-- DATA --}}

                            <td>

                                {{ $sender->created_at
                                    ->format('d/m/Y H:i') }}

                            </td>


                            {{-- ACÇÕES --}}

                            <td>

                                @if($sender->estado === 'PENDENTE')

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm btn-operadora"
                                        data-id="{{ $sender->id }}"
                                        data-sender="{{ $sender->sender }}"
                                    >
                                        <i class="fa fa-send"></i>
                                        Enviar
                                    </button>


                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm btn-rejeitar"
                                        data-id="{{ $sender->id }}"
                                        data-sender="{{ $sender->sender }}"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>

                                @endif


                                @if(
                                    $sender->estado ===
                                    'EM_APROVACAO_OPERADORA'
                                )

                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm btn-aprovar"
                                        data-id="{{ $sender->id }}"
                                        data-sender="{{ $sender->sender }}"
                                    >
                                        <i class="fa fa-check"></i>
                                        Aprovar
                                    </button>


                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm btn-rejeitar"
                                        data-id="{{ $sender->id }}"
                                        data-sender="{{ $sender->sender }}"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>

                                @endif


                                @if($sender->estado === 'APROVADO')

                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm btn-suspender"
                                        data-id="{{ $sender->id }}"
                                        data-sender="{{ $sender->sender }}"
                                    >
                                        <i class="fa fa-ban"></i>
                                        Suspender
                                    </button>

                                @endif


                                @if($sender->estado === 'SUSPENSO')

                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm btn-reactivar"
                                        data-id="{{ $sender->id }}"
                                        data-sender="{{ $sender->sender }}"
                                    >
                                        <i class="fa fa-check"></i>
                                        Reactivar
                                    </button>

                                @endif


                                @if($sender->estado === 'REJEITADO')

                                    <span class="text-muted">
                                        Sem acções
                                    </span>

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

@endsection

@push('js')

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
                $('meta[name="csrf-token"]')
                    .attr('content')

        }

    });


    /*
    |--------------------------------------------------------------------------
    | ENVIAR OPERADORA
    |--------------------------------------------------------------------------
    */

    $('.btn-operadora').click(function () {

        let id = $(this).data('id');


        Swal.fire({

            icon: 'question',

            title: 'Enviar para operadora?',

            text:
                'O Sender ID ficará em aprovação pela operadora.',

            showCancelButton: true,

            confirmButtonText: 'Sim, enviar',

            cancelButtonText: 'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            executarAcao(
                `/admin/sender-ids/${id}/enviar-operadora`,
                'Sender enviado!'
            );

        });

    });


    /*
    |--------------------------------------------------------------------------
    | APROVAR
    |--------------------------------------------------------------------------
    */

    $('.btn-aprovar').click(function () {

        let id = $(this).data('id');


        Swal.fire({

            icon: 'question',

            title: 'Aprovar Sender ID?',

            text:
                'O cliente poderá utilizar este Sender ID para enviar SMS.',

            showCancelButton: true,

            confirmButtonText: 'Sim, aprovar',

            cancelButtonText: 'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            executarAcao(
                `/admin/sender-ids/${id}/aprovar`,
                'Sender aprovado!'
            );

        });

    });


    /*
    |--------------------------------------------------------------------------
    | REJEITAR
    |--------------------------------------------------------------------------
    */

    $('.btn-rejeitar').click(function () {

        let id = $(this).data('id');


        Swal.fire({

            icon: 'warning',

            title: 'Rejeitar Sender ID',

            input: 'textarea',

            inputLabel:
                'Motivo da rejeição',

            inputPlaceholder:
                'Informe o motivo...',

            inputAttributes: {
                maxlength: 1000
            },

            showCancelButton: true,

            confirmButtonText:
                'Rejeitar',

            cancelButtonText:
                'Cancelar',

            inputValidator: function (value) {

                if (!value) {

                    return 'Informe o motivo da rejeição.';

                }

            }

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            executarAcao(
                `/admin/sender-ids/${id}/rejeitar`,
                'Sender rejeitado!',
                {
                    motivo: result.value
                }
            );

        });

    });


    /*
    |--------------------------------------------------------------------------
    | SUSPENDER
    |--------------------------------------------------------------------------
    */

    $('.btn-suspender').click(function () {

        let id = $(this).data('id');


        Swal.fire({

            icon: 'warning',

            title: 'Suspender Sender ID',

            input: 'textarea',

            inputLabel:
                'Motivo da suspensão',

            inputPlaceholder:
                'Informe o motivo...',

            showCancelButton: true,

            confirmButtonText:
                'Suspender',

            cancelButtonText:
                'Cancelar',

            inputValidator: function (value) {

                if (!value) {

                    return 'Informe o motivo da suspensão.';

                }

            }

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            executarAcao(
                `/admin/sender-ids/${id}/suspender`,
                'Sender suspenso!',
                {
                    motivo: result.value
                }
            );

        });

    });


    /*
    |--------------------------------------------------------------------------
    | REACTIVAR
    |--------------------------------------------------------------------------
    */

    $('.btn-reactivar').click(function () {

        let id = $(this).data('id');


        Swal.fire({

            icon: 'question',

            title: 'Reactivar Sender ID?',

            showCancelButton: true,

            confirmButtonText:
                'Sim, reactivar',

            cancelButtonText:
                'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            executarAcao(
                `/admin/sender-ids/${id}/reactivar`,
                'Sender reactivado!'
            );

        });

    });


    /*
    |--------------------------------------------------------------------------
    | EXECUTAR AJAX
    |--------------------------------------------------------------------------
    */

    function executarAcao(
        url,
        titulo,
        dados = {}
    ) {

        $.ajax({

            url: url,

            type: 'POST',

            data: dados,

            success: function (response) {

                if (response.status == 1) {

                    Swal.fire({

                        icon: 'success',

                        title: titulo,

                        text: response.message,

                        timer: 1800,

                        timerProgressBar: true,

                        showConfirmButton: false

                    }).then(function () {

                        window.location.reload();

                    });


                    return;

                }


                Swal.fire({

                    icon: 'warning',

                    title: 'Atenção',

                    text:
                        response.message ??
                        'Não foi possível executar a operação.'

                });

            },


            error: function (xhr) {

                let mensagem =
                    'Ocorreu um erro ao executar a operação.';


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

</script>

@endpush