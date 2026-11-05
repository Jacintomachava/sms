
@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h5>
                <i class="fa fa-user"></i>
                Detalhes do Utilizador
            </h5>

            <a href="{{ route('admin.utilizadores.index') }}"
               class="btn btn-secondary btn-sm">
                Voltar
            </a>

        </div>

        <div class="card-body">

            <table class="table table-bordered">

                <tr>
                    <th width="25%">Nome</th>
                    <td>{{ $utilizador->name }}</td>
                </tr>

                <tr>
                    <th>Email</th>
                    <td>{{ $utilizador->email }}</td>
                </tr>

                <tr>
                    <th>Telefone</th>
                    <td>{{ $utilizador->telefone ?? '—' }}</td>
                </tr>

                <tr>
                    <th>Estado</th>
                    <td>{{ $utilizador->estado }}</td>
                </tr>

                <tr>
                    <th>Último Login</th>
                    <td>
                        {{ $utilizador->ultimo_login_em
                            ? \Carbon\Carbon::parse($utilizador->ultimo_login_em)->format('d/m/Y H:i')
                            : 'Nunca' }}
                    </td>
                </tr>

                <tr>
                    <th>Roles</th>
                    <td>
                        @foreach($roles as $role)
                            <span class="badge badge-light-primary">
                                {{ $role->nome }}
                            </span>
                        @endforeach
                    </td>
                </tr>

            </table>

            <h6 class="mt-4">Permissões da Role</h6>

            <div class="row">
                @forelse($permissoesRole as $permissao)
                    <div class="col-md-4 mb-2">
                        <span class="badge badge-light-info">
                            {{ $permissao->nome }}
                        </span>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted">
                            Nenhuma permissão associada à role.
                        </p>
                    </div>
                @endforelse
            </div>

            <hr>

            <h6>Permissões Directas</h6>

            <div class="row">
                @forelse($permissoesDirectas as $permissao)
                    <div class="col-md-4 mb-2">
                        <span class="badge badge-light-success">
                            {{ $permissao->nome }}
                        </span>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted">
                            Nenhuma permissão directa.
                        </p>
                    </div>
                @endforelse
            </div>

            <hr>

            @php
                $superAdmin = \Illuminate\Support\Facades\DB::table('platform_user_roles as pur')
                    ->join('roles as r', 'r.id', '=', 'pur.role_id')
                    ->where('pur.user_id', auth()->id())
                    ->where('pur.activo', 1)
                    ->where('r.activo', 1)
                    ->where('r.codigo', 'SUPER_ADMIN')
                    ->where('r.scope', 'PLATFORM')
                    ->exists();
            @endphp

            @if($superAdmin)

                <a href="{{ route('admin.utilizadores.edit', $utilizador->id) }}"
                   class="btn btn-primary">
                    <i class="fa fa-pencil"></i>
                    Editar Utilizador
                </a>

                @if($utilizador->id != auth()->id())

                    <form method="POST"
                          action="{{ route('admin.utilizadores.estado', $utilizador->id) }}"
                          class="d-inline">

                        @csrf
                        @method('PATCH')

                        <button type="submit"
                                class="btn {{ $utilizador->estado === 'ACTIVO' ? 'btn-warning' : 'btn-success' }}"
                                onclick="return confirm('Confirma a alteração do estado deste utilizador?')">

                            <i class="fa fa-power-off"></i>

                            {{ $utilizador->estado === 'ACTIVO'
                                ? 'Suspender Utilizador'
                                : 'Activar Utilizador' }}

                        </button>

                    </form>

                @endif

            @endif

        </div>

    </div>

</div>

@endsection
