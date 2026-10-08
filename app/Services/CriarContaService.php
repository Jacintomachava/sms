<?php

namespace App\Services;

use App\Models\Conta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CriarContaService
{
    public function criar(array $dados, string $tipoCobranca = 'PRE_PAGO'): array {

        if (!in_array($tipoCobranca, ['PRE_PAGO', 'POS_PAGO'])) {
            throw new RuntimeException(
                'Tipo de cobrança inválido.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 1. CRIAR UTILIZADOR
        |--------------------------------------------------------------------------
        */
        $user = User::create([
            'name' => trim($dados['name']),
            'email' => strtolower(trim($dados['email'])),
            'telefone' => trim($dados['telefone']),
            'password' => $dados['password'],
            'estado' => 'ACTIVO',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. DADOS DA CONTA
        |--------------------------------------------------------------------------
        */
        if ($dados['tipo_conta'] === 'EMPRESA') {

            $nomeConta = trim($dados['nome_empresa']);
            $nomeLegal = trim($dados['nome_legal']);
            $nuit = trim($dados['nuit']);

        } else {

            $nomeConta = trim($dados['name']);
            $nomeLegal = null;
            $nuit = null;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. CRIAR CONTA
        |--------------------------------------------------------------------------
        */
        $conta = Conta::create([
            'nome' => $nomeConta,
            'tipo' => $dados['tipo_conta'],
            'nome_legal' => $nomeLegal,
            'nuit' => $nuit,
            'email' => strtolower(trim($dados['email'])),
            'telefone' => trim($dados['telefone']),
            'tipo_cobranca' => $tipoCobranca,
            'estado' => 'ACTIVA',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. ROLE OWNER
        |--------------------------------------------------------------------------
        */
        $ownerRole = Role::query()
            ->where('codigo', 'OWNER')
            ->where('scope', 'ACCOUNT')
            ->where('activo', true)
            ->first();

        if (!$ownerRole) {
            throw new RuntimeException(
                'A role OWNER da conta não está configurada.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. ASSOCIAR OWNER À CONTA
        |--------------------------------------------------------------------------
        */
        DB::table('conta_user')->insert([
            'conta_id' => $conta->id,
            'user_id' => $user->id,
            'role_id' => $ownerRole->id,
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'user' => $user,
            'conta' => $conta,
        ];
    }
}