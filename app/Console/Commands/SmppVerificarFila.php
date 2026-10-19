<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SmppVerificarFila extends Command
{
    protected $signature = 'smpp:verificar-fila {--limite=20}';

    protected $description = 'Verificar SMS pendentes na fila SMPP sem enviar';

    public function handle(): int
    {
        $limite = max(1, min(100, (int) $this->option('limite')));

        $sms = DB::table('sms_outbox as o')
            ->join('sms as s', 's.id', '=', 'o.sms_id')
            ->select(
                'o.id as outbox_id',
                's.id as sms_id',
                's.conta_id',
                's.telefone',
                's.segmentos',
                's.custeado_por',
                's.estado as sms_estado',
                'o.estado as outbox_estado'
            )
            ->where('s.estado', 'PENDENTE')
            ->orderBy('o.id')
            ->limit($limite)
            ->get();

        $this->info('INFORDATA SMS - Verificação da fila');
        $this->line('-----------------------------------');

        $this->info(
            'SMS pendentes encontradas: ' . $sms->count()
        );

        foreach ($sms as $item) {

            $reservas = DB::table('sms_reservas')
                ->where('sms_id', $item->sms_id)
                ->where('estado', 'RESERVADA')
                ->selectRaw('tipo, SUM(quantidade) as total')
                ->groupBy('tipo')
                ->pluck('total', 'tipo');

            $cliente = (int) ($reservas['CLIENTE'] ?? 0);
            $stock = (int) ($reservas['STOCK'] ?? 0);
            $segmentos = (int) $item->segmentos;

            $clienteValido = $item->custeado_por === 'CLIENTE'
                ? $cliente === $segmentos
                : (
                    $item->custeado_por === 'INFORDATA'
                    && $cliente === 0
                );

            $stockValido = $stock === $segmentos;

            $valida = $segmentos > 0
                && $clienteValido
                && $stockValido;

            $this->line(sprintf(
                'SMS #%d | Segmentos: %d | Cliente: %d | Stock: %d | %s',
                $item->sms_id,
                $segmentos,
                $cliente,
                $stock,
                $valida ? 'RESERVAS OK' : 'VERIFICAR RESERVAS'
            ));
        }

        $this->warn(
            'Verificação apenas informativa. Nenhuma SMS foi enviada.'
        );

        return self::SUCCESS;
    }
}
