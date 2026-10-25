<?php

declare(strict_types=1);

namespace App\Services\SMPP;

use InvalidArgumentException;

/** Segmentador de preparação, NÃO efectua envios nem cobrança. */
final class SmppUdhSegmentador
{
    public function preparar(string $texto, int $referencia): array
    {
        if ($texto === '' || !mb_check_encoding($texto, 'UTF-8')) {
            throw new InvalidArgumentException('Mensagem vazia ou UTF-8 inválido.');
        }
        if ($referencia < 0 || $referencia > 255) {
            throw new InvalidArgumentException('Referência UDH fora do intervalo 0..255.');
        }

        // Apenas ASCII seguro já validado em campo; outros caracteres usam UTF-16BE.
        $ascii = preg_match('/^[\x20-\x7E]+$/D', $texto) === 1
            && strpbrk($texto, '@$[]\\^{}|~`') === false;
        $chars = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            throw new InvalidArgumentException('Texto UTF-8 inválido.');
        }
        $encoding = $ascii ? 'GSM7' : 'UNICODE';
        $dataCoding = $ascii ? 0 : 8;
        $bytes = $ascii ? strlen($texto) : strlen(mb_convert_encoding($texto, 'UTF-16BE', 'UTF-8'));
        $simples = $ascii ? 160 : 140;
        $multipart = $bytes > $simples;
        $limite = $ascii ? ($multipart ? 134 : 160) : ($multipart ? 134 : 140);
        $partes = [];
        $parte = '';
        foreach ($chars as $char) {
            $codificado = $ascii ? $char : mb_convert_encoding($char, 'UTF-16BE', 'UTF-8');
            if (strlen($codificado) > $limite) {
                throw new InvalidArgumentException('Carácter excede o limite do segmento.');
            }
            if (strlen($parte) + strlen($codificado) > $limite) {
                $partes[] = $parte;
                $parte = '';
            }
            $parte .= $codificado;
        }
        if ($parte !== '') {
            $partes[] = $parte;
        }
        $total = count($partes);
        if ($total > 10) {
            throw new InvalidArgumentException('Máximo operacional: 10 segmentos.');
        }
        $resultado = [];
        foreach ($partes as $indice => $parte) {
            $udh = $total > 1 ? pack('C6', 0x05, 0x00, 0x03, $referencia, $total, $indice + 1) : '';
            $payload = $udh . $parte;
            if (strlen($payload) > 140) {
                throw new InvalidArgumentException('Segmento excede 140 octetos.');
            }
            $resultado[] = [
                'numero' => $indice + 1,
                'total' => $total,
                'esm_class' => $total > 1 ? 0x40 : 0x00,
                'data_coding' => $dataCoding,
                'short_message' => $payload,
                'sm_length' => strlen($payload),
                'encoding' => $encoding,
            ];
        }
        return $resultado;
    }
}
