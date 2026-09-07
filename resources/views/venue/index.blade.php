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
                    Venues
                </h2>
                <div class="card-header-right-icon">
                    <a href="#">
                        <a class="btn btn-pill btn-primary btn-sm" href="{{route('user.create')}}" >Registar Venus</a>
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
                            <th>Telefone</th>
                            <th>Localizacao</th>
                            <th>Data</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($venus as $venu)
                            <tr>
                                <td></td>
                                <td>{{$venu->nome}}</td>
                                <td>{{$venu->telefone}} </td>
                                <td>{{$venu->localizacao}} </td>
                                <td>{{ \Carbon\Carbon::parse($venu->created_at)->format('d-M-Y') }}</td>
                                <td>
                                    <a class="btn btn-warning btn-xs edit-btn" href="#"  >
                                        validar
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


@endpush
