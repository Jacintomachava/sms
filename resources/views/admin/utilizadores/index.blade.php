
@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <div>
                <h5>
                    <i class="fa fa-users"></i>
                    Utilizadores da Plataforma
                </h5>

                <small class="text-muted">
                    Gestão dos utilizadores administrativos da Infordata
                </small>
            </div>

            @php
                $superAdmin = \Illuminate\Support\Facades\DB::table('platform_user_roles as pur')
                    ->join('roles as r', 'r.id', '=', 'pur.role_id')
                    ->where('pur.user_id', auth()->id())
                    ->where('pur.activo', 1)
                    ->where('r.activo', 1)
                    ->where('r.scope', 'PLATFORM')
                    ->where('r.codigo', 'SUPER_ADMIN')
                    ->exists();
            @endphp

            @if($superAdmin)
                <a href="{{ route('admin.utilizadores.create') }}"
                   class="btn btn-primary btn-sm">

                    <i class="fa fa-plus"></i>
                    Novo Utilizador

                </a>
            @endif

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="display" id="basic-1">

                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Último Login</th>
                            <th>Acções</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($utilizadores as $utilizador)

                            <tr>

                                <td>
                                    <strong>{{ $utilizador->name }}</strong>
                                </td>

                                <td>{{ $utilizador->email }}</td>

                                <td>{{ $utilizador->telefone ?? '—' }}</td>

                                <td>
                                    <span class="badge badge-light-primary">
                                        {{ $utilizador->roles }}
                                    </span>
                                </td>

                                <td>
                                    @if($utilizador->estado === 'ACTIVO')
                                        <span class="badge badge-light-success">
                                            Activo
                                        </span>
                                    @elseif($utilizador->estado === 'SUSPENSO')
                                        <span class="badge badge-light-warning">
                                            Suspenso
                                        </span>
                                    @else
                                        <span class="badge badge-light-danger">
                                            {{ $utilizador->estado }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $utilizador->ultimo_login_em
                                        ? \Carbon\Carbon::parse($utilizador->ultimo_login_em)->format('d/m/Y H:i')
                                        : 'Nunca' }}
                                </td>

                                <td>

                                    <a href="{{ route('admin.utilizadores.show', $utilizador->id) }}"
                                       class="btn btn-primary btn-xs"
                                       title="Ver detalhes">

                                        <i class="fa fa-eye"></i>

                                    </a>

                                    @if($superAdmin)

                                        <a href="{{ route('admin.utilizadores.edit', $utilizador->id) }}"
                                           class="btn btn-info btn-xs"
                                           title="Editar">

                                            <i class="fa fa-pencil"></i>

                                        </a>

                                    @endif

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
