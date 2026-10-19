<?php

namespace App\Services\SMPP;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmppDlrService
{
    private const DELIVERED = ['DELIVRD'];

    private const FAILED = [
        'UNDELIV',
        'REJECTD',
        'DELETED',
    ];

    private const EXPIRED = ['EXPIRED'];

    public function processar(array $pdu, ?string $host): int
    {
        $commandId = (int) ($pdu['command_id'] ?? 0);

        if ($commandId !== 0x00000005) {
            throw new RuntimeException(
                'A PDU recebida não é deliver_sm.'
            );
        }

        $body = (string) ($pdu['body'] ?? '');
        $sequence = (int) ($pdu['sequence'] ?? 0);

        if ($sequence < 1) {
            throw new RuntimeException(
                'Sequence number do DLR inválido.'
            );
        }

        $dados = $this->interpretarDeliverSm($body);

        return DB::transaction(function () use (
            $dados,
            $host,
            $sequence,
            $body
        ) {
            /*
            |--------------------------------------------------------------------------
            | 1. GUARDAR O RELATÓRIO
            |--------------------------------------------------------------------------
            */
            $reportId = DB::table('smpp_delivery_reports')
                ->insertGetId([
                    'sms_id' => null,
                    'provider_message_id' => $dados['message_id'],
                    'estado' => $dados['estado'],
                    'network_error_code' => $dados['erro'],
                    'host' => $host,
                    'sequence_number' => $sequence,
                    'raw_receipt' => bin2hex($body),
                    'recebido_em' => now(),
                    'processado_em' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | 2. IDENTIFICAR A SMS
            |--------------------------------------------------------------------------
            */
            if (!$dados['message_id']) {
                return $reportId;
            }

            // Evitar associação ambígua entre fornecedores ou SMS.
            $candidatos = DB::table('sms')
                ->where('provider', 'MOVITEL')
                ->where('provider_message_id', $dados['message_id'])
                ->limit(2)
                ->pluck('id');

            if ($candidatos->count() !== 1) {
                return $reportId;
            }

            $sms = DB::table('sms')
                ->where('id', $candidatos->first())
                ->lockForUpdate()
                ->first();

            if (!$sms) {
                return $reportId;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. ASSOCIAR RELATÓRIO
            |--------------------------------------------------------------------------
            */
            DB::table('smpp_delivery_reports')
                ->where('id', $reportId)
                ->update([
                    'sms_id' => $sms->id,
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | 4. ACTUALIZAR ESTADO
            |--------------------------------------------------------------------------
            */
            $novoEstado = $this->mapearEstado(
                $dados['estado']
            );

            // Sem estado reconhecido: manter para análise.
            if ($novoEstado === null) {
                return $reportId;
            }

            // Não sobrescrever estados terminais.
            if (in_array($sms->estado, [
                'ENTREGUE',
                'FALHADO',
                'EXPIRADO',
                'REJEITADO',
            ], true)) {
                return $reportId;
            }

            // Um DLR não deve confirmar consumo financeiro.
            if ($sms->estado !== 'ENVIADO') {
                return $reportId;
            }

            $actualizacao = [
                'estado' => $novoEstado,
                'dlr_estado' => $dados['estado'],
                'dlr_codigo_erro' => $dados['erro'],
                'dlr_raw' => $dados['raw_text'],
                'dlr_recebido_em' => now(),
                'updated_at' => now(),
            ];

            if ($novoEstado === 'ENTREGUE') {
                $actualizacao['entregue_em'] = now();
            } else {
                $actualizacao['falhado_em'] = now();
            }

            DB::table('sms')
                ->where('id', $sms->id)
                ->update($actualizacao);

            DB::table('smpp_delivery_reports')
                ->where('id', $reportId)
                ->update([
                    'processado_em' => now(),
                    'updated_at' => now(),
                ]);

            return $reportId;

        }, 3);
    }

    private function mapearEstado(?string $estado): ?string
    {
        if (in_array($estado, self::DELIVERED, true)) {
            return 'ENTREGUE';
        }

        if (in_array($estado, self::FAILED, true)) {
            return 'FALHADO';
        }

        if (in_array($estado, self::EXPIRED, true)) {
            return 'EXPIRADO';
        }

        return null;
    }

    private function interpretarDeliverSm(string $body): array
    {
        /*
         * Campos obrigatórios SMPP deliver_sm.
         * É necessário percorrer as C-strings e os campos fixos
         * antes de extrair short_message.
         */
        $offset = 0;

        $readCString = function () use ($body, &$offset): string {
            $end = strpos($body, "\0", $offset);

            if ($end === false) {
                throw new RuntimeException(
                    'deliver_sm contém C-string incompleta.'
                );
            }

            $value = substr($body, $offset, $end - $offset);
            $offset = $end + 1;

            return $value;
        };

        $readByte = function () use ($body, &$offset): int {
            if ($offset >= strlen($body)) {
                throw new RuntimeException(
                    'deliver_sm incompleto.'
                );
            }

            return ord($body[$offset++]);
        };

        $readCString(); // service_type

        $readByte();    // source_addr_ton
        $readByte();    // source_addr_npi
        $readCString(); // source_addr

        $readByte();    // dest_addr_ton
        $readByte();    // dest_addr_npi
        $readCString(); // destination_addr

        $esmClass = $readByte();

        $readByte();    // protocol_id
        $readByte();    // priority_flag

        $readCString(); // schedule_delivery_time
        $readCString(); // validity_period

        $readByte();    // registered_delivery
        $readByte();    // replace_if_present_flag
        $readByte();    // data_coding
        $readByte();    // sm_default_msg_id

        $smLength = $readByte();

        if (strlen($body) - $offset < $smLength) {
            throw new RuntimeException(
                'short_message incompleta.'
            );
        }

        $shortMessage = substr($body, $offset, $smLength);
        $offset += $smLength;

        /*
         * Interpretar TLVs opcionais.
         */
        $tlvs = [];

        while ($offset < strlen($body)) {
            if (strlen($body) - $offset < 4) {
                throw new RuntimeException(
                    'Cabeçalho TLV incompleto.'
                );
            }

            $header = unpack(
                'ntag/nlength',
                substr($body, $offset, 4)
            );

            $offset += 4;

            if (strlen($body) - $offset < $header['length']) {
                throw new RuntimeException(
                    'Valor TLV incompleto.'
                );
            }

            $tlvs[$header['tag']] = substr(
                $body,
                $offset,
                $header['length']
            );

            $offset += $header['length'];
        }

        /*
         * 0x001E = receipted_message_id
         * 0x0427 = message_state
         * 0x0423 = network_error_code
         */
        $messageId = isset($tlvs[0x001E])
            ? rtrim($tlvs[0x001E], "\0")
            : null;

        $estado = null;

        if (isset($tlvs[0x0427]) && strlen($tlvs[0x0427]) === 1) {
            $estado = match (ord($tlvs[0x0427])) {
                2 => 'DELIVRD',
                3 => 'EXPIRED',
                4 => 'DELETED',
                5 => 'UNDELIV',
                8 => 'REJECTD',
                default => null,
            };
        }

        $erro = isset($tlvs[0x0423])
            ? bin2hex($tlvs[0x0423])
            : null;

        /*
         * Fallback para recibos textuais.
         * Usar apenas se a PDU for identificada como recibo
         * pelo bit de mensagem do esm_class.
         */
        $isReceipt = ($esmClass & 0x3C) === 0x04;

        if (!$isReceipt) {
            throw new RuntimeException(
                'deliver_sm não identificado como DLR.'
            );
        }

        if (!$messageId && preg_match(
            '/(?:^|\s)id:(\S+)/i',
            $shortMessage,
            $matches
        )) {
            $messageId = $matches[1];
        }

        if (!$estado && preg_match(
            '/(?:^|\s)stat:(\S+)/i',
            $shortMessage,
            $matches
        )) {
            $estado = strtoupper($matches[1]);
        }

        if (!$erro && preg_match(
            '/(?:^|\s)err:(\S+)/i',
            $shortMessage,
            $matches
        )) {
            $erro = $matches[1];
        }

        return [
            'message_id' => $messageId,
            'estado' => $estado,
            'erro' => $erro,
            'raw_text' => $shortMessage,
        ];
    }
}