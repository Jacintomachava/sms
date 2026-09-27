<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conta;
use App\Models\TarifaSms;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TarifaSmsController extends Controller
{
    public function index()
    {
        $tarifas = TarifaSms::query()
            ->with('conta')
            ->orderByRaw('conta_id IS NOT NULL')
            ->orderBy('conta_id')
            ->orderBy('quantidade_minima')
            ->get();

        /*
         * Contas que poderão receber tarifa exclusiva.
         */
        $contas = Conta::query()
            ->where('estado', 'ACTIVA')
            ->orderBy('nome')
            ->get([
                'id',
                'nome',
                'nome_legal',
                'nuit',
            ]);

        return view('admin.tarifas.index', compact('tarifas', 'contas'));
    }


    public function store(Request $request)
    {
        $dados = $this->validar($request);

        $this->validarFaixa(
            $dados['conta_id'] ?? null,
            (int) $dados['quantidade_minima'],
            isset($dados['quantidade_maxima'])
                ? (int) $dados['quantidade_maxima']
                : null,
            $dados['data_inicio'] ?? null,
            $dados['data_fim'] ?? null
        );

        $dados['publica'] =
            empty($dados['conta_id'])
                ? $request->boolean('publica')
                : false;

        $dados['activo'] = true;

        TarifaSms::create($dados);

        return response()->json([
            'status' => 1,
            'message' => 'Tarifa registada com sucesso.',
        ]);
    }


    public function update(Request $request, TarifaSms $tarifa) {
        
        $dados = $this->validar($request);

        $this->validarFaixa(
            $dados['conta_id'] ?? null,
            (int) $dados['quantidade_minima'],
            isset($dados['quantidade_maxima'])
                ? (int) $dados['quantidade_maxima']
                : null,
            $dados['data_inicio'] ?? null,
            $dados['data_fim'] ?? null,
            $tarifa->id
        );

        $dados['publica'] =
            empty($dados['conta_id'])
                ? $request->boolean('publica')
                : false;

        $tarifa->update($dados);

        return response()->json([
            'status' => 1,
            'message' => 'Tarifa actualizada com sucesso.',
        ]);
    }


    private function validar(Request $request): array
    {
        return $request->validate([
            'conta_id' => ['nullable','integer','exists:contas,id',],
            'nome' => ['required','string','max:150',],
            'quantidade_minima' => ['required','integer','min:1',],
            'quantidade_maxima' => ['nullable','integer','gte:quantidade_minima',],
            'preco_sms' => ['required','numeric','gt:0',],
            'publica' => ['nullable','boolean',],
            'data_inicio' => ['nullable','date',],
            'data_fim' => ['nullable','date','after_or_equal:data_inicio',],
            'observacao' => ['nullable','string',],
        ]);
    }


    /**
     * Impedir duas tarifas aplicáveis à mesma quantidade
     * e ao mesmo contexto (geral ou mesma conta).
     */
    private function validarFaixa(?int $contaId, int $minimo, ?int $maximo, ?string $dataInicio = null, ?string $dataFim = null,?int $ignorarId = null): void {

        $query = TarifaSms::query()->where('activo', true);

        /*
         * Mesmo contexto.
         *
         * conta_id NULL = tarifas gerais
         * conta_id X    = tarifas exclusivas da conta X
         */
        if ($contaId) {

            $query->where('conta_id', $contaId);

        } else {

            $query->whereNull('conta_id');

        }

        if ($ignorarId) {

            $query->where(
                'id',
                '!=',
                $ignorarId
            );

        }

        /*
         * Sobreposição da quantidade:
         *
         * faixa existente começa antes do nosso máximo
         * E
         * termina depois do nosso mínimo.
         */
        $query->where(function ($q) use ( $minimo, $maximo) {

            if ($maximo !== null) {

                $q->where('quantidade_minima', '<=', $maximo);

            }

            $q->where(function ($q2) use ($minimo) {

                $q2->whereNull('quantidade_maxima')
                    ->orWhere('quantidade_maxima', '>=', $minimo);

            });

        });

        /*
         * Sobreposição de vigência.
         */
        $query->where(function ($q) use ($dataFim) {

            if ($dataFim === null) {

                return;
            }

            $q->whereNull('data_inicio')
             ->orWhere('data_inicio','<=', $dataFim);

        });

        $query->where(function ($q) use ($dataInicio) {

            if ($dataInicio === null) {

                return;
            }

            $q
                ->whereNull('data_fim')
                ->orWhere(
                    'data_fim',
                    '>=',
                    $dataInicio
                );
        });

        if ($query->exists()) {

            throw ValidationException::withMessages([
                'quantidade_minima' => 'Já existe uma tarifa activa que entra em conflito com esta faixa de quantidade.',
            ]);

        }
    }

    public function alterarEstado(TarifaSms $tarifa)
    {
        $novoEstado = !$tarifa->activo;

        /*
        * Se estamos a ACTIVAR, precisamos verificar
        * se a tarifa entra em conflito com outra tarifa activa.
        */
        if ($novoEstado) {

            $this->validarFaixa(
                $tarifa->conta_id,
                (int) $tarifa->quantidade_minima,
                $tarifa->quantidade_maxima !== null
                    ? (int) $tarifa->quantidade_maxima
                    : null,
                $tarifa->data_inicio?->format('Y-m-d'),
                $tarifa->data_fim?->format('Y-m-d'),
                $tarifa->id
            );
        }

        $tarifa->update([
            'activo' => $novoEstado,
        ]);

        return response()->json([
            'status' => 1,
            'message' => $novoEstado ? 'Tarifa activada com sucesso.' : 'Tarifa inactivada com sucesso.',
            'activo' => $novoEstado,
        ]);
    }
}