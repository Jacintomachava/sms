<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SmppVerificarFila extends Command
{
    protected $signature = 'smpp:verificar-fila {--limite=20}';

    protected $description =
        'Verificar SMS pendentes na fila SMPP sem enviar';

    public function handle(): int
    {
        $limite = max(
            1,
            min(100, (int) $this->option('limite'))
        );

        $sms = DB::table('sms_outbox as o')
            ->join('sms as s', 's.id', '=', 'o.sms_id')
            ->select(
                'o.id as outbox_id',
                's.id as sms_id',
                's.conta_id',
                's.telefone',
                's.mensagem',
                's.segmentos',
                's.custeado_por',
                's.estado as sms_estado',
                'o.estado as outbox_estado'
            )
            ->where('o.estado', 'PENDENTE')
            ->where('s.estado', 'PENDENTE')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('smpp_tentativas_envio as t')
                    ->whereColumn('t.sms_id', 's.id');
            })
            ->orderBy('o.id')
            ->limit($limite)
            ->get();

        $this->info('INFORDATA SMS - Verificação da fila');
        $this->line('-----------------------------------');

        $this->info(
            'SMS candidatas encontradas: ' . $sms->count()
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

            $clienteValido = match ($item->custeado_por) {
                'CLIENTE' => $cliente === $segmentos,
                'INFORDATA' => $cliente === 0,
                default => false,
            };

            $stockValido = $stock === $segmentos;

            $reservasValidas =
                $segmentos > 0 &&
                $clienteValido &&
                $stockValido;

            /*
             * Compatibilidade com a implementação
             * actual do SmppService.
             */
            $mensagem = (string) $item->mensagem;

            $mensagemValida =
                $segmentos === 1 &&
                $mensagem !== '' &&
                strlen($mensagem) <= 160 &&
                !preg_match('/[^\x20-\x7E]/', $mensagem) &&
                strpbrk($mensagem, '@$[]\\^{}|~`') === false;

            /*
             * O fluxo financeiro de confirmação
             * ainda suporta apenas CLIENTE.
             */
            $financiamentoValido =
                $item->custeado_por === 'CLIENTE';

            $valida =
                $reservasValidas &&
                $mensagemValida &&
                $financiamentoValido;

            $this->line(sprintf(
                'SMS #%d | Segmentos: %d | Cliente: %d | Stock: %d | %s',
                $item->sms_id,
                $segmentos,
                $cliente,
                $stock,
                $valida ? 'ELEGÍVEL' : 'NÃO ELEGÍVEL'
            ));

            if (!$reservasValidas) {
                $this->warn('  - Verificar reservas financeiras.');
            }

            if (!$mensagemValida) {
                $this->warn('  - Mensagem incompatível com SMPP actual.');
            }

            if (!$financiamentoValido) {
                $this->warn('  - Financiamento ainda não suportado.');
            }
        }

        $this->warn(
            'Verificação informativa. Nenhuma SMS foi enviada.'
        );

        return self::SUCCESS;
    }
}
