@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="page-title">
        <div class="row">

            <div class="col-6">
                <h4>Enviar SMS</h4>
            </div>

        </div>
    </div>

</div>


<div class="container-fluid">

    <div class="row">

        {{-- FORMULÁRIO --}}
        <div class="col-xl-7">

            <div class="card">

                <div class="card-header">
                    <h4>Nova mensagem</h4>
                </div>

                <div class="card-body">

                    <form
                        id="formEnviarSms"
                        method="POST"
                        action="{{ route('sms.enviar') }}"
                    >

                        @csrf


                        {{-- SENDER --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Sender ID
                            </label>

                            <select
                                name="sender_id"
                                id="sender_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Seleccione
                                </option>

                                @foreach($senders as $sender)

                                    <option value="{{ $sender->id }}">
                                        {{ $sender->sender }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- TELEFONE --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Destinatário
                            </label>

                            <input
                                type="text"
                                name="telefone"
                                id="telefone"
                                class="form-control"
                                placeholder="84xxxxxxx"
                                maxlength="15"
                                required
                            >

                        </div>


                        {{-- MENSAGEM --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Mensagem
                            </label>

                            <textarea
                                name="mensagem"
                                id="mensagem"
                                class="form-control"
                                rows="7"
                                maxlength="5000"
                                placeholder="Digite a mensagem..."
                                required
                            ></textarea>

                        </div>


                        {{-- CONTADOR --}}
                        <div class="row">

                            <div class="col-md-4">

                                <strong>Caracteres:</strong>

                                <span id="totalCaracteres">
                                    0
                                </span>

                            </div>

                            <div class="col-md-4">

                                <strong>Segmentos:</strong>

                                <span id="totalSegmentos">
                                    0
                                </span>

                            </div>

                            <div class="col-md-4">

                                <strong>Codificação:</strong>

                                <span id="encoding">
                                    -
                                </span>

                            </div>

                        </div>


                        <hr>


                        <button
                            type="submit"
                            id="btnEnviarSms"
                            class="btn btn-primary"
                        >
                            <i class="fa fa-paper-plane"></i>
                            Enviar SMS
                        </button>

                    </form>

                </div>

            </div>

        </div>


        {{-- RESUMO --}}
        <div class="col-xl-5">

            <div class="card">

                <div class="card-header">
                    <h4>Resumo</h4>
                </div>

                <div class="card-body">

                    {{-- =====================================================
                        PRE-PAGO
                    ====================================================== --}}
                    @if($conta->tipo_cobranca === 'PRE_PAGO')

                        <p>
                            <strong>Saldo:</strong>

                            <span id="saldoActual">
                                {{ number_format(
                                    $saldoSms,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </span>

                            SMS
                        </p>

                        <p>
                            <strong>SMS a consumir:</strong>

                            <span id="resumoSegmentos">
                                0
                            </span>
                        </p>

                        <p>
                            <strong>Saldo após envio:</strong>

                            <span id="saldoDepois">
                                {{ number_format(
                                    $saldoSms,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </span>
                        </p>

                    {{-- =====================================================
                        POS-PAGO
                    ====================================================== --}}
                    @elseif($conta->tipo_cobranca === 'POS_PAGO')

                        <p>
                            <strong>Consumo no mês:</strong>

                            <span id="consumoActual">
                                {{ number_format(
                                    $consumoPeriodo,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </span>

                            SMS
                        </p>

                        <p>
                            <strong>SMS desta mensagem:</strong>

                            <span id="resumoSegmentos">
                                0
                            </span>
                        </p>

                        <p>
                            <strong>Consumo após envio:</strong>

                            <span id="consumoDepois">
                                {{ number_format(
                                    $consumoPeriodo,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </span>

                            SMS
                        </p>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>



@endsection


@section('script')

<script>

$(document).ready(function () {

    const tipoCobranca = @json($conta->tipo_cobranca);

    let saldoActual = {{ (int) $saldoSms }};

    let consumoActual = {{ (int) $consumoPeriodo }};

    let timer = null;


    $('#mensagem').on('input', function () {

        clearTimeout(timer);

        let mensagem = $(this).val();

        if (!mensagem.length) {

            $('#totalCaracteres').text(0);
            $('#totalSegmentos').text(0);
            $('#resumoSegmentos').text(0);
            $('#encoding').text('-');

            if (tipoCobranca === 'PRE_PAGO') {

                $('#saldoDepois').text(
                    saldoActual.toLocaleString()
                );

            } else if (tipoCobranca === 'POS_PAGO') {

                $('#consumoDepois').text(
                    consumoActual.toLocaleString()
                );
            }

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

                    $('#encoding').text(dados.encoding);


                    /*
                    |--------------------------------------------------------------------------
                    | PRE-PAGO
                    |--------------------------------------------------------------------------
                    */
                    if (tipoCobranca === 'PRE_PAGO') {

                        let saldoDepois = saldoActual - dados.segmentos;

                        $('#saldoDepois').text(
                            saldoDepois.toLocaleString()
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | POS-PAGO
                    |--------------------------------------------------------------------------
                    */
                    else if (tipoCobranca === 'POS_PAGO') {

                        let consumoDepois = consumoActual + dados.segmentos;

                        $('#consumoDepois').text(
                            consumoDepois.toLocaleString()
                        );
                    }

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