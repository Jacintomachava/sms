<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UtilizadorController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | AUTORIZAÇÃO PLATFORM
    |--------------------------------------------------------------------------
    */

    private function exigirPlatform(): void
    {
        abort_unless(
            session('contexto') === 'PLATFORM',
            403,
            'Acesso exclusivo à Administração Infordata.'
        );

        abort_unless(
            $this->temRole('SUPER_ADMIN') ||
            $this->temRole('ADMIN') ||
            $this->temPermissao('platform.users.view'),
            403
        );
    }

    private function temRole(string $codigo): bool
    {
        return DB::table('platform_user_roles as pur')
            ->join('roles as r', 'r.id', '=', 'pur.role_id')
            ->where('pur.user_id', Auth::id())
            ->where('pur.activo', 1)
            ->where('r.activo', 1)
            ->where('r.scope', 'PLATFORM')
            ->where('r.codigo', $codigo)
            ->exists();
    }

    private function temPermissao(string $codigo): bool
    {
        $directa = DB::table('platform_user_permissions as pup')
            ->join('permissions as p', 'p.id', '=', 'pup.permission_id')
            ->where('pup.user_id', Auth::id())
            ->where('p.codigo', $codigo)
            ->where('p.scope', 'PLATFORM')
            ->where('p.activo', 1)
            ->exists();

        if ($directa) {
            return true;
        }

        return DB::table('platform_user_roles as pur')
            ->join('roles as r', 'r.id', '=', 'pur.role_id')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'r.id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('pur.user_id', Auth::id())
            ->where('pur.activo', 1)
            ->where('r.activo', 1)
            ->where('r.scope', 'PLATFORM')
            ->where('p.scope', 'PLATFORM')
            ->where('p.activo', 1)
            ->where('p.codigo', $codigo)
            ->exists();
    }

    private function exigirSuperAdmin(): void
    {
        $this->exigirPlatform();

        abort_unless(
            $this->temRole('SUPER_ADMIN'),
            403,
            'Operação reservada ao SUPER_ADMIN.'
        );
    }

    private function obterUtilizador(int $id)
    {
        $utilizador = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$utilizador) {
            abort(404, 'Utilizador não encontrado.');
        }

        return $utilizador;
    }

    private function rolesDoUtilizador(int $id)
    {
        return DB::table('platform_user_roles as pur')
            ->join('roles as r', 'r.id', '=', 'pur.role_id')
            ->where('pur.user_id', $id)
            ->where('pur.activo', 1)
            ->where('r.scope', 'PLATFORM')
            ->select('r.id', 'r.codigo', 'r.nome')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $this->exigirPlatform();

        $utilizadores = DB::table('users as u')
            ->join('platform_user_roles as pur', function ($join) {
                $join->on('pur.user_id', '=', 'u.id')
                    ->where('pur.activo', 1);
            })
            ->join('roles as r', function ($join) {
                $join->on('r.id', '=', 'pur.role_id')
                    ->where('r.scope', 'PLATFORM');
            })
            ->whereNull('u.deleted_at')
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.telefone',
                'u.estado',
                'u.ultimo_login_em',
                'u.created_at'
            )
            ->selectRaw(
                "GROUP_CONCAT(DISTINCT r.nome ORDER BY r.nome SEPARATOR ', ') as roles"
            )
            ->groupBy(
                'u.id',
                'u.name',
                'u.email',
                'u.telefone',
                'u.estado',
                'u.ultimo_login_em',
                'u.created_at'
            )
            ->orderByDesc('u.id')
            ->get();

        return view('admin.utilizadores.index', compact('utilizadores'));
    }

    /*
    |--------------------------------------------------------------------------
    | CRIAR
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $this->exigirSuperAdmin();

        $roles = DB::table('roles')
            ->where('scope', 'PLATFORM')
            ->where('activo', 1)
            ->orderBy('nome')
            ->get();

        $permissoes = DB::table('permissions')
            ->where('scope', 'PLATFORM')
            ->where('activo', 1)
            ->orderBy('modulo')
            ->orderBy('nome')
            ->get();

        return view('admin.utilizadores.create', compact(
            'roles',
            'permissoes'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | GUARDAR
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $this->exigirSuperAdmin();

        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->where('scope', 'PLATFORM')
                        ->where('activo', 1);
                }),
            ],
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where(function ($query) {
                    $query->where('scope', 'PLATFORM')
                        ->where('activo', 1);
                }),
            ],
        ]);

        DB::transaction(function () use ($dados) {

            $agora = now();

            $userId = DB::table('users')->insertGetId([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'] ?? null,
                'password' => Hash::make($dados['password']),
                'estado' => 'ACTIVO',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

            DB::table('platform_user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $dados['role_id'],
                'activo' => 1,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

            $role = DB::table('roles')
                ->where('id', $dados['role_id'])
                ->first();

            if ($role->codigo === 'PERSONALIZADO') {

                foreach (array_unique($dados['permissoes'] ?? []) as $permissionId) {
                    DB::table('platform_user_permissions')->insert([
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.utilizadores.index')
            ->with('success', 'Utilizador criado com sucesso.');
    }

    /*
    |--------------------------------------------------------------------------
    | DETALHES
    |--------------------------------------------------------------------------
    */

    public function show(int $id)
    {
        $this->exigirPlatform();

        dd('ENTROU NO SHOW', $id);

        $utilizador = $this->obterUtilizador($id);

        $roles = $this->rolesDoUtilizador($id);

        abort_if($roles->isEmpty(), 404);

        $permissoesDirectas = DB::table('platform_user_permissions as pup')
            ->join('permissions as p', 'p.id', '=', 'pup.permission_id')
            ->where('pup.user_id', $id)
            ->where('p.scope', 'PLATFORM')
            ->select('p.codigo', 'p.nome', 'p.modulo')
            ->orderBy('p.modulo')
            ->get();

        $permissoesRole = DB::table('platform_user_roles as pur')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'pur.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('pur.user_id', $id)
            ->where('pur.activo', 1)
            ->where('p.scope', 'PLATFORM')
            ->select('p.codigo', 'p.nome', 'p.modulo')
            ->distinct()
            ->orderBy('p.modulo')
            ->get();

        return view('admin.utilizadores.show', compact(
            'utilizador',
            'roles',
            'permissoesDirectas',
            'permissoesRole'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | EDITAR
    |--------------------------------------------------------------------------
    */

    public function edit(int $id)
    {
        $this->exigirSuperAdmin();

        $utilizador = $this->obterUtilizador($id);

        $rolesActuais = $this->rolesDoUtilizador($id);

        abort_if($rolesActuais->isEmpty(), 404);

        $roleActual = $rolesActuais->first();

        $roles = DB::table('roles')
            ->where('scope', 'PLATFORM')
            ->where('activo', 1)
            ->orderBy('nome')
            ->get();

        $permissoes = DB::table('permissions')
            ->where('scope', 'PLATFORM')
            ->where('activo', 1)
            ->orderBy('modulo')
            ->orderBy('nome')
            ->get();

        $permissoesSeleccionadas = DB::table('platform_user_permissions')
            ->where('user_id', $id)
            ->pluck('permission_id')
            ->toArray();

        return view('admin.utilizadores.edit', compact(
            'utilizador',
            'roleActual',
            'roles',
            'permissoes',
            'permissoesSeleccionadas'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, int $id)
    {
        $this->exigirSuperAdmin();

        $utilizador = $this->obterUtilizador($id);

        abort_if($this->rolesDoUtilizador($id)->isEmpty(), 404);

        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'telefone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->where('scope', 'PLATFORM')
                        ->where('activo', 1);
                }),
            ],
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where(function ($query) {
                    $query->where('scope', 'PLATFORM')
                        ->where('activo', 1);
                }),
            ],
        ]);

        $novoRole = DB::table('roles')
            ->where('id', $dados['role_id'])
            ->first();

        if ($id === (int) Auth::id() && $novoRole->codigo !== 'SUPER_ADMIN') {
            throw ValidationException::withMessages([
                'role_id' => 'Não podes retirar o teu próprio acesso SUPER_ADMIN.',
            ]);
        }

        DB::transaction(function () use ($id, $dados, $novoRole) {

            $actualizacao = [
                'name' => $dados['name'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'] ?? null,
                'updated_at' => now(),
            ];

            if (!empty($dados['password'])) {
                $actualizacao['password'] = Hash::make($dados['password']);
            }

            DB::table('users')
                ->where('id', $id)
                ->update($actualizacao);

            DB::table('platform_user_roles')
                ->where('user_id', $id)
                ->update([
                    'activo' => 0,
                    'updated_at' => now(),
                ]);

            $associacao = DB::table('platform_user_roles')
                ->where('user_id', $id)
                ->where('role_id', $dados['role_id'])
                ->first();

            if ($associacao) {
                DB::table('platform_user_roles')
                    ->where('id', $associacao->id)
                    ->update([
                        'activo' => 1,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('platform_user_roles')->insert([
                    'user_id' => $id,
                    'role_id' => $dados['role_id'],
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('platform_user_permissions')
                ->where('user_id', $id)
                ->delete();

            if ($novoRole->codigo === 'PERSONALIZADO') {

                foreach (array_unique($dados['permissoes'] ?? []) as $permissionId) {
                    DB::table('platform_user_permissions')->insert([
                        'user_id' => $id,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.utilizadores.show', $id)
            ->with('success', 'Utilizador actualizado com sucesso.');
    }

    /*
    |--------------------------------------------------------------------------
    | ALTERAR ESTADO
    |--------------------------------------------------------------------------
    */

    public function alterarEstado(int $id)
    {
        $this->exigirSuperAdmin();

        $utilizador = $this->obterUtilizador($id);

        abort_if(
            $id === (int) Auth::id(),
            422,
            'Não podes suspender a tua própria conta.'
        );

        $novoEstado = $utilizador->estado === 'ACTIVO'
            ? 'SUSPENSO'
            : 'ACTIVO';

        DB::table('users')
            ->where('id', $id)
            ->update([
                'estado' => $novoEstado,
                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            'Estado do utilizador actualizado com sucesso.'
        );
    }
}
