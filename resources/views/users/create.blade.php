@extends('layouts.app')

@push('css')
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
@endpush

@section('conteudo')

  <div class="container-fluid">
    <div class="row">
      <div class="col-xl-12">
        <div class="card height-equal title-line">
          <div class="card-header">
            <h2>Registar Utilizador </h2>
            <p class="f-m-light mt-1">
                Registar Utilizador da Administracao
            </p>
          </div>
          <div class="card-body basic-wizard important-validation">
            <div id="msform1">
              <form class="row g-3 needs-validation custom-input" id="form" enctype="multipart/form-data">

                @csrf
                
                <form1 class="stepper-one row g-3 needs-validation custom-input" >

                     <input class="form-control"  type="hidden" name="user"   >

                     <div class="col-sm-4">
                        <label class="form-label" for="email-basic-wizard">Primeiro Nome<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="first_name"  placeholder="Primeiro Nome"  >
                      </div>

                      <div class="col-sm-4">
                        <label class="form-label" for="firstnamewizard">Ultimo Nome<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="last_name"  placeholder="Ultimo Nome" >
                      </div>

                     <div class="col-sm-4">
                        <label class="form-label" for="firstnamewizard">Username<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="username"  placeholder="Username" >
                      </div>

                      <div class="col-sm-4">
                        <label class="form-label" for="email-basic-wizard">Telefone<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="phone"  placeholder="Telefone"  >
                      </div>

                       <div class="col-4">
                        <label class="form-label" for="passwordwizard">Nivel<span class="txt-danger">*</span></label>
                        <select class="form-select" name="role">
                            <option value="" >Seleciona o Nivel</option>
                            <option value="Validator" >Validator</option>
                            <option value="Admin" >Admin</option>
                        </select>
                      </div>

                      <div class="col-4">
                        <label class="form-label" for="passwordwizard">Estado<span class="txt-danger">*</span></label>
                        <select class="form-select" name="status">
                            <option value="1" >Activo</option>
                            <option value="0" >Inativo</option>
                        </select>
                      </div>

                </form1>
                <div class="wizard-footer d-flex gap-2 justify-content-end">
                    <button id="botao_salvar" type="submit" class="btn btn-success w-100">
                        <span id="botao_texto">{{__('Registar Utilizador')}}</span>
                        <i id="icon_enviar" class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                    </button>
                </div>
                </form>  
              </div>
              <br>
          </div>
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
        rules: {
            first_name: {
                required: true,
                minlength: 2
            },
            last_name: {
                required: true,
                minlength: 2
            },
            phone: {
                required: true,
                minlength: 9,
                maxlength: 9
            },
            username: {
                required: true,
                minlength: 3
            },
            role: {
                required: true
            }
        },
        submitHandler: function(form) {
            // Use FormData para suportar envio de arquivos
            var formData = new FormData(form);

            $.ajax({
                type: "POST",
                url: "{{ route('user.store') }}",
                data: formData,
                processData: false, // Não processa os dados, necessário para FormData
                contentType: false, // Define o content type como multipart/form-data
                beforeSend: function() {
                    $('#botao_salvar').attr('disabled', true);
                    $('#icon_enviar').removeClass('ri-arrow-right-line').addClass('spinner-border ri-loader-2-line');
                    $('#botao_texto').text('Alterando Senha...');
                },
                success: function(response) {
                    $('#botao_salvar').attr('disabled', false);
                    $('#icon_enviar').removeClass('spinner-border ri-loader-2-line').addClass('ri-arrow-right-line');
                    $('#botao_texto').text('Alterar Senha');

                    if (response.status == 1) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: response.message,
                        });
                        window.location.reload();
                    } else if (response.status == 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: response.message,
                        });
                    }
                },
                error: function(errors) {
                    $('#botao_salvar').attr('disabled', false);
                    $('#icon_enviar').removeClass('spinner-border ri-loader-2-line').addClass('ri-arrow-right-line');
                    $('#botao_texto').text('Alterar Senha');
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

@endpush
