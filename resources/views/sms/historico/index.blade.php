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
                    href="{{ route('sms.index') }}"
                    class="btn btn-primary btn-nova-tarifa"

                >
                    <i class="fa fa-plus"></i>
                    Nova SMS
                </a>

            </div>

        </div>

    </div>

</div>


<div class="row">

    <div class="col-sm-12">

        <div class="card">

            <div class="card-header">
                <h4>Últimos SMS</h4>
            </div>

            <div class="card-body">

                <div class="table-responsive custom-scrollbar">

                    <table
                        class="display"
                        id="basic-1"
                    >

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Sender</th>
                                <th>Destinatário</th>
                                <th>Segmentos</th>
                                <th>Estado</th>
                            </tr>

                        </thead>

                        <tbody>

                            @foreach($sms as $item)

                                <tr>

                                    <td>
                                        {{ $item->id }}
                                    </td>

                                    <td>
                                        {{ $item->created_at->format('d/m/Y H:i') }}
                                    </td>

                                    <td>
                                        {{ $item->sender?->sender }}
                                    </td>

                                    <td>
                                        {{ $item->telefone }}
                                    </td>

                                    <td>
                                        {{ $item->segmentos }}
                                    </td>

                                    <td>

                                        @switch($item->estado)

                                            @case('ENTREGUE')
                                                <span class="badge badge-light-success">
                                                    Entregue
                                                </span>
                                                @break

                                            @case('ENVIADO')
                                                <span class="badge badge-light-primary">
                                                    Enviado
                                                </span>
                                                @break

                                            @case('FALHADO')
                                            @case('REJEITADO')
                                                <span class="badge badge-light-danger">
                                                    {{ ucfirst(strtolower($item->estado)) }}
                                                </span>
                                                @break

                                            @default
                                                <span class="badge badge-light-warning">
                                                    {{ ucfirst(strtolower($item->estado)) }}
                                                </span>

                                        @endswitch

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@section('script')

<script>

$(document).ready(function () {

    let saldoActual = {{ (int) $saldoSms }};

    let timer = null;


    $('#mensagem').on('input', function () {

        clearTimeout(timer);

        let mensagem = $(this).val();

        if (!mensagem.length) {

            $('#totalCaracteres').text(0);
            $('#totalSegmentos').text(0);
            $('#resumoSegmentos').text(0);
            $('#encoding').text('-');
            $('#saldoDepois').text(
                saldoActual.toLocaleString()
            );

            return;
        }


        timer = setTimeout(function () {

            $.ajax({

                url: "{{ route('sms.calcular') }}",

                type: "POST",

                data: {
                    _token: "{{ csrf_token() }}",
                    mensagem: mensagem
                },

                success: function (response) {

                    let dados = response.data;

                    $('#totalCaracteres')
                        .text(dados.caracteres);

                    $('#totalSegmentos')
                        .text(dados.segmentos);

                    $('#resumoSegmentos')
                        .text(dados.segmentos);

                    $('#encoding')
                        .text(dados.encoding);


                    let saldoDepois =
                        saldoActual -
                        dados.segmentos;

                    $('#saldoDepois').text(
                        saldoDepois.toLocaleString()
                    );

                }

            });

        }, 250);

    });


    $('#formEnviarSms').on(
        'submit',
        function (e) {

            e.preventDefault();

            let form = $(this);

            let botao =
                $('#btnEnviarSms');


            botao.prop(
                'disabled',
                true
            );


            Swal.fire({
                title: 'A processar...',
                text: 'A preparar a mensagem.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });


            $.ajax({

                url: form.attr('action'),

                type: 'POST',

                data: form.serialize(),

                success: function (response) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Mensagem registada',
                        text: response.message
                    }).then(function () {

                        window.location.reload();

                    });

                },

                error: function (xhr) {

                    botao.prop(
                        'disabled',
                        false
                    );


                    let mensagem =
                        xhr.responseJSON?.message
                        ?? 'Não foi possível processar a mensagem.';


                    if (
                        xhr.status === 422 &&
                        xhr.responseJSON?.errors
                    ) {

                        let errors =
                            xhr.responseJSON.errors;

                        let chave =
                            Object.keys(errors)[0];

                        if (chave) {
                            mensagem =
                                errors[chave][0];
                        }

                    }


                    Swal.fire({
                        icon: 'error',
                        title: 'Envio não concluído',
                        text: mensagem
                    });

                }

            });

        }
    );

});

</script>

@endsection