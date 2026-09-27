<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Models\GrupoContacto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactoController extends Controller
{
    public function index()
    {
        $contaId = session('conta_id');

        $contactos = Contacto::query()
            ->where('conta_id', $contaId)
            ->with('grupos:id,nome')
            ->latest()
            ->get();

        $grupos = GrupoContacto::query()
            ->where('conta_id', $contaId)
            ->where('estado', 'ACTIVO')
            ->orderBy('nome')
            ->get();

        return view('contactos.index', compact('contactos', 'grupos'));
    }


    public function store(Request $request)
    {
        $contaId = session('conta_id');

        $request->validate([
            'nome' => ['required','string','max:150'],
            'telefone' => ['required','string','max:20',
                Rule::unique('contactos', 'telefone')
                    ->where(fn ($query) => $query->where('conta_id', $contaId))
            ],
            'email' => ['nullable','email','max:150'],
            'grupos' => ['nullable','array'],
            'grupos.*' => ['integer'],
            'data_nascimento' => ['nullable','date','before_or_equal:today',],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalizar telefone
        |--------------------------------------------------------------------------
        */
        $telefone = preg_replace('/\D+/','', $request->telefone);

        $contacto = Contacto::create([
            'conta_id' => $contaId,
            'nome' => $request->nome,
            'telefone' => $telefone,
            'email' => $request->email,
            'data_nascimento' => $request->data_nascimento,
            'estado' => 'ACTIVO',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validar grupos pertencentes à conta
        |--------------------------------------------------------------------------
        */
        if ($request->filled('grupos')) {

            $grupos = GrupoContacto::query()
                ->where('conta_id', $contaId)
                ->whereIn('id', $request->grupos)
                ->pluck('id');

            $contacto->grupos()->sync($grupos);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Contacto registado com sucesso.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $contaId = session('conta_id');
        $contacto = Contacto::query()->where('conta_id', $contaId)->findOrFail($id);
        $telefone = preg_replace('/\D+/','', $request->telefone);

        if (strlen($telefone) === 12 && str_starts_with($telefone, '258')) {
            $telefone = substr($telefone, 3);
        }

        $request->merge(['telefone' => $telefone]);

        $request->validate([
            'nome' => ['required','string','max:150'],
            'telefone' => ['required','regex:/^(82|83|84|85|86|87)[0-9]{7}$/',
                Rule::unique('contactos', 'telefone')
                    ->where(fn ($query) => $query->where('conta_id', $contaId))
                    ->ignore($contacto->id)
            ],
            'email' => ['nullable','email','max:150'],
            'data_nascimento' => ['nullable','date','before_or_equal:today'],
            'grupos' => ['nullable','array'],
            'grupos.*' => ['integer']
        ]);

        $contacto->update([
            'nome' => $request->nome,
            'telefone' => $telefone,
            'email' => $request->email,
            'data_nascimento' => $request->data_nascimento,
        ]);

        /*
        * Só aceita grupos da própria conta.
        */
        $grupos = GrupoContacto::query()
            ->where('conta_id', $contaId)
            ->whereIn('id', $request->input('grupos', []))
            ->pluck('id');

        $contacto->grupos()->sync($grupos);

        return response()->json([
            'status' => 1,
            'message' => 'Contacto actualizado com sucesso.'
        ]);
    }

    public function alterarEstado($id)
    {
        $contacto = Contacto::query()->where('conta_id', session('conta_id'))->findOrFail($id);

        $contacto->estado = $contacto->estado === 'ACTIVO' ? 'INACTIVO': 'ACTIVO';

        $contacto->save();

        return response()->json([
            'status' => 1,
            'message' => $contacto->estado === 'ACTIVO' ? 'Contacto activado com sucesso.': 'Contacto inactivado com sucesso.',
            'estado' => $contacto->estado
        ]);
    }

    public function destroy($id)
    {
        $contacto = Contacto::query()->where('conta_id', session('conta_id'))->findOrFail($id);

        $contacto->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Contacto removido com sucesso.'
        ]);
    }
}