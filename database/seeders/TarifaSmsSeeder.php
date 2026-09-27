<?php

namespace Database\Seeders;

use App\Models\TarifaSms;
use Illuminate\Database\Seeder;

class TarifaSmsSeeder extends Seeder
{
    public function run(): void
    {
        TarifaSms::updateOrCreate(
            [
                'conta_id' => null,
                'quantidade_minima' => 1,
                'quantidade_maxima' => 100000,
            ],
            [
                'nome' => 'Tarifa Geral 1 - 100.000 SMS',
                'preco_sms' => 1.2500,
                'publica' => true,
                'activo' => true,
            ]
        );

        TarifaSms::updateOrCreate(
            [
                'conta_id' => null,
                'quantidade_minima' => 100001,
                'quantidade_maxima' => 300000,
            ],
            [
                'nome' => 'Tarifa Geral 100.001 - 300.000 SMS',
                'preco_sms' => 1.1500,
                'publica' => true,
                'activo' => true,
            ]
        );


        TarifaSms::updateOrCreate(
            [
                'conta_id' => null,
                'quantidade_minima' => 300001,
                'quantidade_maxima' => null,
            ],
            [
                'nome' => 'Tarifa Geral acima de 300.000 SMS',
                'preco_sms' => 1.0500,
                'publica' => true,
                'activo' => true,
            ]
        );
    }
}