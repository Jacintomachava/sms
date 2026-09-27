@extends('layouts.app')

@section('conteudo')

<div class="col-sm-12">

    <div class="card">

        <div class="card-body">

            <h4>
                Administração da Plataforma
            </h4>

            <p>
                Bem-vindo,
                {{ auth()->user()->name }}.
            </p>

        </div>

    </div>

</div>

@endsection