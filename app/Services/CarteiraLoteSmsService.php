<?php

namespace App\Services;

use App\Models\CarteiraLoteSms;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CarteiraLoteSmsService
{
    /**
     * Consultar os créditos comerciais disponíveis
     * de uma conta.
     *
     * O saldo livre é:
     *
     * quantidade_disponivel - quantidade_reservada
     */
    public function saldoLivre(int $contaId): int
    {
        $lotes = CarteiraLoteSms::query()
            ->where('conta_id', $contaId)
            ->where('estado', 'ACTIVO')
            ->get([
                'quantidade_disponivel',
                'quantidade_reservada',
            ]);

        return $lotes->sum(function ($lote) {
            return max(
                0,
                (int) $lote->quantidade_disponivel
                - (int) $lote->quantidade_reservada
            );
        });
    }

    /**
     * Consultar lotes comerciais activos
     * por ordem FIFO.
     */
    public function listarLotesDisponiveis(int $contaId)
    {
        return CarteiraLoteSms::query()
            ->where('conta_id', $contaId)
            ->where('estado', 'ACTIVO')
            ->whereRaw(
                'quantidade_disponivel > quantidade_reservada'
            )
            ->orderBy('creditado_em')
            ->orderBy('id')
            ->get();
    }

    /**
     * Validar se a conta possui créditos livres
     * suficientes para uma determinada quantidade
     * de segmentos SMS.
     *
     * Esta consulta é informativa.
     * A reserva efectiva deve ser feita pelo
     * SmsReservaService dentro de uma transacção.
     */
    public function validarSaldo(
        int $contaId,
        int $segmentos
    ): void {
        if ($segmentos <= 0) {
            throw new InvalidArgumentException(
                'A quantidade de segmentos deve ser superior a zero.'
            );
        }

        $saldoLivre = $this->saldoLivre($contaId);

        if ($saldoLivre < $segmentos) {
            throw new RuntimeException(
                "Saldo de lotes SMS insuficiente. " .
                "Disponível: {$saldoLivre}; " .
                "Necessário: {$segmentos}."
            );
        }
    }

    /**
     * Consultar o resumo dos lotes comerciais
     * de uma conta.
     */
    public function resumo(int $contaId): array
    {
        $lotes = CarteiraLoteSms::query()
            ->where('conta_id', $contaId)
            ->get([
                'quantidade_disponivel',
                'quantidade_reservada',
            ]);

        $disponivel = (int) $lotes->sum(
            'quantidade_disponivel'
        );

        $reservado = (int) $lotes->sum(
            'quantidade_reservada'
        );

        return [
            'conta_id' => $contaId,
            'saldo_sms' => $disponivel,
            'saldo_reservado_sms' => $reservado,
            'saldo_livre_sms' => max(
                0,
                $disponivel - $reservado
            ),
        ];
    }

    /**
     * Método antigo de consumo directo.
     *
     * Mantido para detectar chamadas antigas
     * que ainda não foram migradas.
     *
     * No novo fluxo:
     *
     * 1. SmsReservaService reserva créditos.
     * 2. A SMS é submetida à Movitel.
     * 3. SmsConfirmacaoService consome reservas
     *    quando submit_sm_resp indica aceitação.
     */
    public function consumir(
        int $contaId,
        int $smsId,
        int $segmentos
    ): array {
        throw new RuntimeException(
            'Consumo directo de créditos desactivado. ' .
            'O consumo deve ser realizado através ' .
            'do SmsConfirmacaoService após a ' .
            'confirmação SMPP.'
        );
    }
}
