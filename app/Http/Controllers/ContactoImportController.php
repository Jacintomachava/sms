<?php

namespace App\Http\Controllers;

use App\Exports\ContactosModeloExport;
use App\Imports\ContactosImport;
use App\Models\GrupoContacto;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactoImportController extends Controller
{
    /**
     * Baixar modelo Excel.
     */
    public function modelo()
    {
        return Excel::download(
            new ContactosModeloExport(),
            'modelo_importacao_contactos.xlsx'
        );
    }


    /**
     * Importar contactos.
     */
    public function importar(Request $request)
    {
        $contaId = session('conta_id');

        /*
        |--------------------------------------------------------------------------
        | VALIDAR REQUEST
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'ficheiro' => ['required','file','mimes:xlsx,xls','max:10240',],
            'grupo_id' => ['nullable','integer',],
        ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDAR GRUPO
        |--------------------------------------------------------------------------
        |
        | O grupo precisa obrigatoriamente pertencer
        | à conta autenticada.
        |
        */
        $grupoId = null;

        if ($request->filled('grupo_id')) {

            $grupo = GrupoContacto::query()
                ->where('conta_id', $contaId)
                ->where('estado', 'ACTIVO')
                ->findOrFail($request->grupo_id);

            $grupoId = $grupo->id;
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTAR
        |--------------------------------------------------------------------------
        */
        try {

            $import = new ContactosImport($contaId, $grupoId);

            Excel::import($import, $request->file('ficheiro'));

            /*
            |--------------------------------------------------------------------------
            | RESULTADO
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'status' => 1,
                'message' => 'Importação concluída com sucesso.',
                'total' => $import->getTotal(),
                'importados' => $import->getImportados(),
                'duplicados' => $import->getDuplicados(),
                'invalidos' => $import->getInvalidos(),
                'erros' => $import->getErros(),
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível processar o ficheiro Excel.',
            ], 500);
        }
    }
}