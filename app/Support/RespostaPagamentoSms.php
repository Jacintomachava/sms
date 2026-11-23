<?php

namespace App\Support;

class RespostaPagamentoSms
{
    public static function formatar(array $resultado): array
    {
        $estado = $resultado['estado_pagamento']
            ?? $resultado['transacao']?->estado
            ?? 'INCERTA';

        $sucesso = (bool) ($resultado['sucesso'] ?? false);

        $confirmadoECreditado = $sucesso && $estado === 'SUCESSO';

        $pendente = in_array($estado, [
            'INICIADA',
            'PROCESSANDO',
            'AGUARDANDO_CONFIRMACAO',
            'INCERTA',
        ], true);

        $podeTentar = !$confirmadoECreditado
            && !$pendente
            && (bool) ($resultado['pode_tentar_novamente'] ?? false);

        return [
            'status' => $confirmadoECreditado ? 1 : 0,
            'sucesso' => $confirmadoECreditado,
            'message' => $resultado['message']
                ?? 'Não foi possível concluir o pagamento.',
            'estado_pagamento' => $estado,
            'pagamento_pendente' => $pendente,
            'pode_tentar_novamente' => $podeTentar,
            'transacao_id' => $resultado['transacao']?->id,
            'pagamento_id' => $resultado['pagamento']?->id,
        ];
    }

    public static function httpStatus(array $dados): int
    {
        if ($dados['sucesso']) {
            return 200;
        }

        if ($dados['pagamento_pendente']) {
            return 202;
        }

        if ($dados['estado_pagamento'] === 'SUCESSO') {
            return 202;
        }

        return 422;
    }
}