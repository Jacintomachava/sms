@extends('layouts.app')

@push('css')
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
@endpush

@section('conteudo')

<div class="col-xl-12 order-md-iii">
    <div class="card title-line overflow-hidden member-wrapper">
        <div class="card-header card-no-border">
            <div class="header-top">
                <h2>
                    <img class="img-40 img-fluid m-r-20" src="../assets/images/job-search/2.jpg" alt="">
                    Clientes de SMS
                </h2>
                <div class="card-header-right-icon">
                    <a href="#">
                        <a class="btn btn-pill btn-primary btn-sm" href="{{route('cliente.create')}}" >Registar Cliente</a>
                    </a>
                </div>
            </div>
        </div>
    </div> <!-- Fechamento da div "card title-line overflow-hidden member-wrapper" -->
</div> <!-- Fechamento da div "col-xl-12 order-md-iii" -->

<div class="col-sm-12" style="margin-top: -2%">
    <div class="card">
        <div class="card-header pb-0 card-no-border">
        </div>
        <div class="card-body">

            <div class="table-responsive custom-scrollbar">
                <table class="display" id="basic-1">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Codigo</th>
                            <th>Sistema</th>
                            <th>Sender</th>
                            <th>Representante</th>
                            <th>Cargo</th>
                            <th>Telefone</th>
                            <th>Tipo</th>
                            <th>Saldo</th>
                            <th>Data R.</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($clientes as $cliente)
                            <tr>
                                <td></td>
                                <td>{{$cliente->codigo}}</td>
                                <td>{{$cliente->sistema}}</td>
                                <td>{{$cliente->sender}} </td>
                                <td>{{$cliente->representante}} </td>
                                <td>{{$cliente->cargo}} </td>
                                <td>{{$cliente->telefone}} </td>
                                <td>{{$cliente->tipo}} </td>
                                <td>{{$cliente->saldo_sms}} </td>
                                <td>{{ \Carbon\Carbon::parse($cliente->created_at)->format('d-M-Y') }}</td>
                                <td>
                                    <a class="btn btn-warning btn-xs" href="{{route('cliente.edit',['id'=>$cliente->id])}}"  >
                                        Editar 
                                    </a>
                                    <a class="btn btn-danger btn-xs activate-btn" data-id="{{ $cliente->id }}" >
                                        Remover
                                    </a>
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

$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#form").validate({
        // Adicionar regras para cada campo
        rules: {
            disciplina: {
                required: true
            }
        },
        submitHandler: function(form) {
            $.ajax({
                type: "POST",
                url: "",
                data: $(form).serialize(), // Corrigido para usar `form` em vez de `this`

                beforeSend: function () {
                    // Desabilita o botão de envio e altera o ícone para mostrar que está autenticando
                    $('#botao_salvar').attr('disabled', true);
                    $('#icon_enviar').removeClass('ri-arrow-right-line').addClass('spinner-border ri-loader-2-line');
                    $('#botao_texto').text('Gravando Tipo Post...');
                },

                success: function(response) {
                    // Habilita o botão e retorna o ícone original
                    $('#botao_salvar').attr('disabled', false);
                    $('#icon_enviar').removeClass('spinner-border ri-loader-2-line').addClass('ri-arrow-right-line');
                    $('#botao_texto').text('Gravar');

                    // Redireciona ou exibe uma mensagem de erro com base na resposta
                    if(response.status == 1) {

                      Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: response.message,
                      });

                      window.location.reload();

                    } else if(response.status == 0) {

                      Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: response.message,
                      });

                    }
                },
                error: function(errors) {
                    // Habilita o botão e retorna o ícone original em caso de erro
                    $('#botao_salvar').attr('disabled', false);
                    $('#icon_enviar').removeClass('spinner-border ri-loader-2-line').addClass('ri-arrow-right-line');
                    $('#botao_texto').text('Gravar');

                    // Exibe a mensagem de erro
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: errors.responseJSON.message,
                    });

                }
            });
        }
    });
});

</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const activateButtons = document.querySelectorAll('.activate-btn');

        activateButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');

                Swal.fire({
                    title: 'Tem certeza?',
                    text: "Já não tera mais este Cliente? ",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, Retira!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar requisição AJAX
                        fetch(`/retirar/type/post/${id}`, {
                            method: 'get',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire(
                                    'Retirado!',
                                    data.message,
                                    'success'
                                );
                                // Recarregar a página para atualizar o estado
                                location.reload();
                            } else {
                                Swal.fire(
                                    'Erro!',
                                    data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(() => {
                            Swal.fire(
                                'Erro!',
                                'Ocorreu um erro ao processar sua solicitação.',
                                'error'
                            );
                        });
                    }
                });
            });
        });
    });
</script>

@endpush
