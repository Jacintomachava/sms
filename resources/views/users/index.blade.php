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
                    Utilizador Administradores
                </h2>
                <div class="card-header-right-icon">
                    <a href="#">
                        <a class="btn btn-pill btn-primary btn-sm" href="{{route('user.create')}}" >Registar Utilizador</a>
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
                            <th>Nome</th>
                            <th>Username</th>
                            <th>Estado</th>
                            <th>Telefone</th>
                            <th>Role</th>
                            <th>Data R.</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($users as $user)
                            <tr>
                                <td></td>
                                <td>{{$user->nome}}</td>
                                <td>{{$user->username}} </td>
                                <td>
                                    @if($user->estado==1)
                                      <a class="btn btn-success btn-xs" href="#!">Acivo</a>
                                    @else
                                      <a class="btn btn-danger btn-xs" href="#!">Inacivo</a>
                                    @endif
                                </td>
                                <td>{{$user->telefone}} </td>
                                <td>{{$user->role}} </td>
                                <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d-M-Y') }}</td>
                                <td>
                                    <a class="btn btn-warning btn-xs edit-btn" href="#"  >
                                        Editar 
                                    </a>
                                    <a class="btn btn-danger btn-xs activate-btn" data-id="{{ $user->id }}" >
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

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form  id="form">  
        <div class="modal-header">
            <h4 class="modal-title" id="myExtraLargeModal">Registar Type de Post</h4>
            <button class="btn-close py-0" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body dark-modal">

            @csrf

            <div class="row">
                
                <div class="col-12">
                    <label class="form-label" for="confirmpasswordwizard">Type de Post</label>
                    <input class="form-control"  type="text" name="name" placeholder="Tipo de Post" >
                </div>

            </div>

        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" id="botao_salvar" type="submit" >
                <span id="botao_texto">{{__('Registar Type Post')}}</span>
                <i id="icon_enviar" class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
            </button>
        </div>
      </form>  
    </div>
    </div>
</div>

<div class="modal fade bd-example-modal-lg1" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form  id="form1">  
        <div class="modal-header">
            <h4 class="modal-title" id="myExtraLargeModal1">Actaulizar Type de Post</h4>
            <button class="btn-close py-0" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body dark-modal">

            @csrf

            <input type="text" name="postTypeId" id="postTypeId">

            <div class="row">
                
                <div class="col-12">
                    <label class="form-label" for="confirmpasswordwizard">Type de Post</label>
                    <input class="form-control"  type="text" name="postTypeName" id="postTypeName" placeholder="Tipo de Post" >
                </div>

            </div>

        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" id="botao_salvar" type="submit" >
                <span id="botao_texto1">{{__('Actaulizar Type Post')}}</span>
                <i id="icon_enviar" class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
            </button>
        </div>
      </form>  
    </div>
    </div>
</div>

@endsection

@push('js')

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Clica em "Editar"
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            // Preenche os campos
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            document.getElementById('postTypeId').value = id;
            document.getElementById('postTypeName').value = name;

            // Atualiza título e botão
            document.getElementById('myExtraLargeModal1').innerText = "Editar Type de Post";
            document.getElementById('botao_texto1').innerText = "Actualizar Type Post";

            // Abre o modal
            const modal = new bootstrap.Modal(document.querySelector('.bd-example-modal-lg1'));
            modal.show();
        });
    });

    // Envio do formulário (criar ou editar)
    document.getElementById('form1').addEventListener('submit', function (e) {
        
        e.preventDefault();

        const id = document.getElementById('postTypeId').value;
        const name = document.getElementById('postTypeName').value;
        const url = id ? `/post-type/${id}` : `/post-type`; // se tiver ID, faz update

        fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro ao salvar!');
            }
        })
        .catch(err => console.error(err));
    });
});
</script>


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
                    text: "Já não tera mais este Tipo? ",
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
