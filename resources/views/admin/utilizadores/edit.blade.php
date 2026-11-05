@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="card">

        <div class="card-header">
            <h5>Editar Utilizador: {{ $utilizador->name }}</h5>
        </div>

        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('admin.utilizadores.update', $utilizador->id) }}">

                @csrf
                @method('PUT')

                @include('admin.utilizadores._form')

            </form>

        </div>

    </div>

</div>

@endsection