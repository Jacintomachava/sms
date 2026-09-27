<?php

namespace App\Http\Controllers;

use App\Models\GrupoContacto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrupoContactoController extends Controller
{
    public function index()
    {
        $contaId = session('conta_id');

        $grupos = GrupoContacto::query()
            ->where('conta_id', $contaId)
            ->withCount('contactos')
            ->latest()
            ->get();

        return view('grupos_contactos.index', compact('grupos'));
    }


    public function store(Request $request)
    {
        $contaId = session('conta_id');

        $request->validate([
            'nome' => ['required','string','max:150',
                Rule::unique('grupos_contactos','nome')->where(fn ($query) =>
                    $query->where('conta_id', $contaId)
                )
            ],
            'descricao' => ['nullable','string','max:255'
            ]
        ]);

        GrupoContacto::create([
            'conta_id' => $contaId,
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'estado' => 'ACTIVO',
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Grupo criado com sucesso.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $contaId = session('conta_id');

        $grupo = GrupoContacto::query()->where('conta_id', $contaId)->findOrFail($id);

        $request->validate([
            'nome' => ['required','string','max:150',
                Rule::unique('grupos_contactos','nome')
                ->where(fn ($query) => $query->where('conta_id',$contaId))
                ->ignore($grupo->id)
            ],
            'descricao' => ['nullable','string','max:255'
            ]
        ]);


        $grupo->update([
            'nome' => $request->nome,
            'descricao' => $request->descricao
        ]);


        return response()->json([
            'status' => 1,
            'message' => 'Grupo actualizado com sucesso.'
        ]);
    }

    public function alterarEstado($id)
    {
        $grupo = GrupoContacto::query()->where('conta_id', session('conta_id'))->findOrFail($id);

        $grupo->estado = $grupo->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        $grupo->save();

        return response()->json([
            'status' => 1,
            'message' => $grupo->estado === 'ACTIVO' ? 'Grupo activado com sucesso.' : 'Grupo inactivado com sucesso.',
            'estado' => $grupo->estado
        ]);
    }

    public function destroy($id)
    {
        $grupo = GrupoContacto::query()->where('conta_id', session('conta_id'))->findOrFail($id);
        $grupo->contactos()->detach();
        $grupo->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Grupo removido com sucesso.'
        ]);
    }
}