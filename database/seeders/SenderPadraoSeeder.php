<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SenderPadraoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | 1. LOCALIZAR OU CRIAR SENDER INFOSMS
            |--------------------------------------------------------------------------
            */
            $sender = DB::table('sender_ids')
                ->where('sender', 'InfoSMS')
                ->first();

            if ($sender) {

                // Não alterar silenciosamente um Sender já existente
                // que possa ter outro proprietário ou finalidade.
                if ($sender->tipo !== 'COMPARTILHADO') {
                    throw new RuntimeException(
                        'O Sender InfoSMS já existe como EXCLUSIVO. '
                        . 'Confirme a sua utilização antes de o converter.'
                    );
                }

                $senderId = $sender->id;

                DB::table('sender_ids')
                    ->where('id', $senderId)
                    ->update([
                        'descricao' => 'Sender partilhado padrão da plataforma InfoSMS',
                        'updated_at' => now(),
                    ]);

            } else {

                $senderId = DB::table('sender_ids')
                    ->insertGetId([
                        'sender' => 'InfoSMS',
                        'descricao' => 'Sender partilhado padrão da plataforma InfoSMS',
                        'tipo' => 'COMPARTILHADO',
                        'estado' => 'APROVADO',
                        'aprovado_em' => now(),
                        'observacao' => 'Sender padrão da plataforma. Confirmar autorização da operadora antes de utilização em produção.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. VALIDAR ESTADO GLOBAL
            |--------------------------------------------------------------------------
            */
            $senderActual = DB::table('sender_ids')
                ->where('id', $senderId)
                ->first();

            if ($senderActual->estado !== 'APROVADO') {
                throw new RuntimeException(
                    'O Sender InfoSMS existe, mas não está APROVADO.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. ASSOCIAR ÀS CONTAS EXISTENTES
            |--------------------------------------------------------------------------
            */
            DB::table('contas')
                ->select('id')
                ->orderBy('id')
                ->chunkById(100, function ($contas) use ($senderId) {

                    foreach ($contas as $conta) {

                        DB::table('conta_sender_ids')
                            ->insertOrIgnore([
                                'conta_id' => $conta->id,
                                'sender_id' => $senderId,
                                'estado' => 'ACTIVO',
                                'atribuido_em' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                });

            $this->command?->info(
                'Sender InfoSMS configurado e associado às contas existentes.'
            );
        });
    }
}
