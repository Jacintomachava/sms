
@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="mb-4">
        <h4>Configuração OTP</h4>
        <p class="text-muted">
            Configure o envio e a validação de códigos OTP da sua conta.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">
            <h5 class="mb-0">Definições de OTP</h5>
        </div>

        <div class="card-body">

            <form method="POST"
                  action="{{ route('configuracao.update') }}">

                @csrf
                @method('PUT')

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Sender ID
                        </label>

                        <select name="sender_id"
                                class="form-control"
                                required>

                            <option value="">
                                Seleccione um Sender
                            </option>

                            @foreach($senders as $sender)

                                <option value="{{ $sender->id }}"
                                    @selected(
                                        old('sender_id', $configuracao->sender_id)
                                        == $sender->id
                                    )>

                                    {{ $sender->sender }}

                                    @if($sender->tipo === 'COMPARTILHADO')
                                        (Partilhado)
                                    @endif

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Estado do OTP
                        </label>

                        <select name="activo"
                                class="form-control">

                            <option value="1"
                                @selected(old('activo', (int) $configuracao->activo) == 1)>
                                ACTIVO
                            </option>

                            <option value="0"
                                @selected(old('activo', (int) $configuracao->activo) == 0)>
                                INACTIVO
                            </option>

                        </select>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Quantidade de dígitos
                        </label>

                        <input type="number"
                               name="tamanho_codigo"
                               class="form-control"
                               min="4"
                               max="8"
                               value="{{ old('tamanho_codigo', $configuracao->tamanho_codigo) }}"
                               required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Validade do código (minutos)
                        </label>

                        <input type="number"
                               name="validade_minutos"
                               class="form-control"
                               min="1"
                               max="30"
                               value="{{ old('validade_minutos', $configuracao->validade_minutos) }}"
                               required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Máximo de tentativas
                        </label>

                        <input type="number"
                               name="max_tentativas"
                               class="form-control"
                               min="1"
                               max="10"
                               value="{{ old('max_tentativas', $configuracao->max_tentativas) }}"
                               required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Intervalo de reenvio (segundos)
                        </label>

                        <input type="number"
                               name="intervalo_reenvio_segundos"
                               class="form-control"
                               min="30"
                               max="600"
                               value="{{ old(
                                   'intervalo_reenvio_segundos',
                                   $configuracao->intervalo_reenvio_segundos
                               ) }}"
                               required>

                    </div>

                </div>

                <div class="mt-3">

                    <button type="submit"
                            class="btn btn-primary">
                        Guardar Configuração
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

@endsection
