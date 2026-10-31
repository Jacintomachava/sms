@extends('layouts.app')

@section('conteudo')

@php
    $coresEstado = [
        'PENDENTE'    => 'secondary',
        'PROCESSANDO' => 'info',
        'INCERTO'     => 'warning',
        'ENVIADO'     => 'primary',
        'ENTREGUE'    => 'success',
        'FALHADO'     => 'danger',
        'REJEITADO'   => 'danger',
        'EXPIRADO'    => 'dark',
    ];

    $coresDlr = [
        'DELIVRD' => 'success',
        'ENTREGUE' => 'success',
        'UNDELIV' => 'danger',
        'REJECTD' => 'danger',
        'EXPIRED' => 'danger',
        'ENROUTE' => 'info',
        'ACCEPTD' => 'info',
    ];
@endphp

<div class="container-fluid">

    {{-- ====================================================== --}}
    {{-- CABEÇALHO --}}
    {{-- ====================================================== --}}

    <div class="row">
        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div>
                            <h4 class="card-title mb-1">
                                Monitorização Global de SMS
                            </h4>

                            <p class="text-muted mb-0">
                                Consulta de mensagens de todas as contas da Infordata
                            </p>
                        </div>

                        <span class="badge badge-light-primary">
                            Total encontrado:
                            {{ number_format($sms->total(), 0, ',', '.') }}
                        </span>

                    </div>

                </div>

            </div>

        </div>
    </div>


    {{-- ====================================================== --}}
    {{-- FILTROS --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-filter"></i>
                        Filtros de Pesquisa
                    </h5>
                </div>

                <div class="card-body">

                    <form
                        method="GET"
                        action="{{ route('admin.sms.index') }}"
                    >

                        <div class="row g-3">

                            {{-- CONTA --}}

                            <div class="col-md-4">

                                <label class="form-label">
                                    Conta
                                </label>

                                <select
                                    name="conta_id"
                                    class="form-select"
                                >

                                    <option value="">
                                        Todas as contas
                                    </option>

                                    @foreach($contas as $conta)

                                        <option
                                            value="{{ $conta->id }}"
                                            @selected(request('conta_id') == $conta->id)
                                        >
                                            {{ $conta->nome_legal ?: $conta->nome }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- ESTADO --}}

                            <div class="col-md-2">

                                <label class="form-label">
                                    Estado
                                </label>

                                <select
                                    name="estado"
                                    class="form-select"
                                >

                                    <option value="">
                                        Todos
                                    </option>

                                    @foreach($estados as $estado)

                                        <option
                                            value="{{ $estado }}"
                                            @selected(request('estado') == $estado)
                                        >
                                            {{ $estado }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- TELEFONE --}}

                            <div class="col-md-2">

                                <label class="form-label">
                                    Telefone
                                </label>

                                <input
                                    type="text"
                                    name="telefone"
                                    class="form-control"
                                    placeholder="84XXXXXXX"
                                    value="{{ request('telefone') }}"
                                >

                            </div>


                            {{-- DATA INICIAL --}}

                            <div class="col-md-2">

                                <label class="form-label">
                                    Data Inicial
                                </label>

                                <input
                                    type="date"
                                    name="inicio"
                                    class="form-control"
                                    value="{{ request('inicio') }}"
                                >

                            </div>


                            {{-- DATA FINAL --}}

                            <div class="col-md-2">

                                <label class="form-label">
                                    Data Final
                                </label>

                                <input
                                    type="date"
                                    name="fim"
                                    class="form-control"
                                    value="{{ request('fim') }}"
                                >

                            </div>

                        </div>


                        <div class="mt-3 d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary btn-sm"
                            >
                                <i class="fa fa-search"></i>
                                Pesquisar
                            </button>

                            <a
                                href="{{ route('admin.sms.index') }}"
                                class="btn btn-secondary btn-sm"
                            >
                                <i class="fa fa-refresh"></i>
                                Limpar
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- LISTAGEM GLOBAL --}}
    {{-- ====================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">
                        Listagem de SMS
                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-responsive custom-scrollbar">

                        <table
                            class="display"
                            id="basic-1"
                        >

                            <thead>

                                <tr>
                                    <th>ID</th>
                                    <th>Conta</th>
                                    <th>Telefone</th>
                                    <th>Mensagem</th>
                                    <th>Segmentos</th>
                                    <th>Origem</th>
                                    <th>Estado</th>
                                    <th>DLR</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Acção</th>
                                </tr>

                            </thead>

                            <tbody>

                                @foreach($sms as $item)

                                    @php

                                        $corEstado = $coresEstado[$item->estado]
                                            ?? 'secondary';

                                        $corDlr = $coresDlr[
                                            strtoupper($item->dlr_estado ?? '')
                                        ] ?? 'secondary';

                                        $nomeConta = $item->conta_nome_legal
                                            ?: ($item->conta_nome ?? 'Conta #' . $item->conta_id);

                                        $dataCriacao = $item->created_at
                                            ? \Carbon\Carbon::parse($item->created_at)
                                            : null;

                                    @endphp

                                    <tr>

                                        {{-- ID --}}

                                        <td>
                                            <strong>
                                                #{{ $item->id }}
                                            </strong>
                                        </td>


                                        {{-- CONTA --}}

                                        <td>

                                            <strong>
                                                {{ $nomeConta }}
                                            </strong>

                                            <br>

                                            <small class="text-muted">
                                                {{ $item->tipo_cobranca ?? '-' }}
                                            </small>

                                        </td>


                                        {{-- TELEFONE --}}

                                        <td>
                                            {{ $item->telefone }}
                                        </td>


                                        {{-- MENSAGEM COM TOOLTIP --}}

                                        <td
                                            data-container="body"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-original-title="{{ $item->mensagem }}"
                                            style="cursor: pointer;"
                                        >

                                            {{ \Illuminate\Support\Str::limit($item->mensagem, 30) }}

                                        </td>


                                        {{-- SEGMENTOS --}}

                                        <td>

                                            <span class="badge badge-light-primary">
                                                {{ $item->segmentos }}
                                            </span>

                                        </td>


                                        {{-- ORIGEM --}}

                                        <td>

                                            <span class="badge badge-light-info">
                                                {{ $item->origem }}
                                            </span>

                                        </td>


                                        {{-- ESTADO --}}

                                        <td>

                                            <span class="badge bg-{{ $corEstado }}">
                                                {{ $item->estado }}
                                            </span>

                                        </td>


                                        {{-- DLR --}}

                                        <td>

                                            @if($item->dlr_estado)

                                                <span class="badge bg-{{ $corDlr }}">
                                                    {{ $item->dlr_estado }}
                                                </span>

                                            @else

                                                <span class="badge bg-secondary">
                                                    Sem DLR
                                                </span>

                                            @endif

                                        </td>


                                        {{-- DATA --}}

                                        <td data-order="{{ $dataCriacao?->format('Y-m-d') ?? '' }}">

                                            {{ $dataCriacao?->format('d/m/Y') ?? '-' }}

                                        </td>


                                        {{-- HORA --}}

                                        <td>

                                            {{ $dataCriacao?->format('H:i:s') ?? '-' }}

                                        </td>


                                        {{-- ACÇÃO --}}

                                        <td>

                                            <a
                                                href="{{ route('admin.sms.show', $item->id) }}"
                                                class="btn btn-primary btn-xs"
                                                data-container="body"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-original-title="Ver detalhes do SMS"
                                            >

                                                <i class="fa fa-eye"></i>
                                                Detalhes

                                            </a>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINAÇÃO DO LARAVEL --}}

                    <div class="mt-4">

                        {{ $sms->links() }}

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | TOOLTIP BOOTSTRAP
    |--------------------------------------------------------------------------
    */

    if (typeof bootstrap !== 'undefined') {

        document.querySelectorAll(
            '[data-bs-toggle="tooltip"]'
        ).forEach(function (element) {

            if (!bootstrap.Tooltip.getInstance(element)) {

                new bootstrap.Tooltip(element, {
                    container: 'body'
                });

            }

        });

    }

});
</script>

@endpush