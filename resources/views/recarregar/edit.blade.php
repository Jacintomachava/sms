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
            <h2>Editar Cliente </h2>
            <p class="f-m-light mt-1">
                Editar Cliente
            </p>
          </div>
          <div class="card-body basic-wizard important-validation">
            <div id="msform1">
              <form class="row g-3 needs-validation custom-input" id="form" enctype="multipart/form-data">

                @csrf
                
                <form1 class="stepper-one row g-3 needs-validation custom-input" >

                     <input class="form-control"  type="hidden" name="id"  value="{{$cliente->id}}" >

                     <div class="col-sm-4">
                        <label class="form-label" for="email-basic-wizard">Sistema<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="sistema"  placeholder="Sistema" value="{{$cliente->sistema}}" >
                      </div>

                      <div class="col-sm-4">
                        <label class="form-label" for="firstnamewizard">Sender<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="sender"  placeholder="Sender" value="{{$cliente->sender}}" >
                      </div>

                     <div class="col-sm-4">
                        <label class="form-label" for="firstnamewizard">Representante<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="representante"  placeholder="Representante" value="{{$cliente->representante}}" >
                      </div>

                      <div class="col-sm-4">
                        <label class="form-label" for="email-basic-wizard">Telefone<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="telefone"  placeholder="Telefone" value="{{$cliente->telefone}}" >
                      </div>

                     <div class="col-sm-4">
                        <label class="form-label" for="email-basic-wizard">Cargo<span class="txt-danger">*</span></label>
                        <input class="form-control"  type="text" name="cargo"  placeholder="Cargo" value="{{$cliente->cargo}}" >
                      </div>

                      <div class="col-4">
                        <label class="form-label" for="passwordwizard">Tipo<span class="txt-danger">*</span></label>
                        <select class="form-select" name="tipo">
                            <option value="pago">Pago</option>
                            <option value="gratuito">Grátis</option>
                        </select>
                      </div>

                </form1>
                <div class="wizard-footer d-flex gap-2 justify-content-end">
                    <button id="botao_salvar" type="submit" class="btn btn-success w-100">
                        <span id="botao_texto">{{__('Editar Cliente')}}</span>
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
            sistema: {
                required: true,
                minlength: 2
            },
            sender: {
                required: true,
                minlength: 2
            },
            telefone: {
                required: true,
                minlength: 9,
                maxlength: 9
            },
            representante: {
                required: true,
                minlength: 3
            },
            cargo: {
                required: true
            }
        },
        submitHandler: function(form) {
            // Use FormData para suportar envio de arquivos
            var formData = new FormData(form);

            $.ajax({
                type: "POST",
                url: "{{ route('cliente.store') }}",
                data: formData,
                processData: false, // Não processa os dados, necessário para FormData
                contentType: false, // Define o content type como multipart/form-data
                beforeSend: function() {
                    $('#botao_salvar').attr('disabled', true);
                    $('#icon_enviar').removeClass('ri-arrow-right-line').addClass('spinner-border ri-loader-2-line');
                    $('#botao_texto').text('Registar Cliente...');
                },
                success: function(response) {
                    $('#botao_salvar').attr('disabled', false);
                    $('#icon_enviar').removeClass('spinner-border ri-loader-2-line').addClass('ri-arrow-right-line');
                    $('#botao_texto').text('Registar Cliente');

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
                    $('#botao_texto').text('Registar Cliente');
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
