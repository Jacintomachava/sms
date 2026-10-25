<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Contagem conservadora alinhada ao transporte SMPP testado:
 * ASCII sem compactação e Unicode UTF-16BE.
 * O segmentador UDH deve aplicar exactamente as mesmas regras.
 */
class SmsSegmentService
{
    private const MAX_SEGMENTOS = 10;
    private const ASCII_SIMPLES = 160;
    private const ASCII_MULTIPART = 134; // 140 bytes - 6 bytes UDH
    private const UTF16_SIMPLES = 70;
    private const UTF16_MULTIPART = 67; // 134 bytes / 2

    public function calcular(string $texto): array
    {
        if ($texto === '') {
            throw new InvalidArgumentException('A mensagem SMS não pode estar vazia.');
        }

        if (!mb_check_encoding($texto, 'UTF-8')) {
            throw new InvalidArgumentException('A mensagem deve utilizar UTF-8 válido.');
        }

        $caracteres = mb_strlen($texto, 'UTF-8');

        // Apenas ASCII transportável como bytes sem packing GSM7.
        // Caracteres GSM 03.38 fora de ASCII precisam de conversão específica;
        // por segurança, seguem pela via Unicode.
        $ascii = preg_match('/^[\x20-\x7E\r\n]*$/D', $texto) === 1;

        if ($ascii) {
            $unidades = strlen($texto);
            $segmentos = $unidades <= self::ASCII_SIMPLES
                ? 1
                : (int) ceil($unidades / self::ASCII_MULTIPART);
            $encoding = 'GSM7';
        } else {
            $encoding = 'UNICODE';
            $utf16 = mb_convert_encoding($texto, 'UTF-16BE', 'UTF-8');
            $unidades = intdiv(strlen($utf16), 2);

            if ($unidades <= self::UTF16_SIMPLES) {
                $segmentos = 1;
            } else {
                // Não dividir pares substitutos (emojis e outros suplementares).
                $chars = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY);
                if ($chars === false) {
                    throw new InvalidArgumentException('Não foi possível analisar a mensagem.');
                }

                $segmentos = 1;
                $ocupacao = 0;
                foreach ($chars as $char) {
                    $custo = intdiv(strlen(mb_convert_encoding($char, 'UTF-16BE', 'UTF-8')), 2);
                    if ($custo > self::UTF16_MULTIPART) {
                        throw new InvalidArgumentException('Carácter Unicode demasiado longo.');
                    }
                    if ($ocupacao + $custo > self::UTF16_MULTIPART) {
                        $segmentos++;
                        $ocupacao = 0;
                    }
                    $ocupacao += $custo;
                }
            }
        }

        if ($segmentos > self::MAX_SEGMENTOS) {
            throw new InvalidArgumentException(
                'A mensagem excede o limite de ' . self::MAX_SEGMENTOS . ' segmentos SMS.'
            );
        }

        return [
            'encoding' => $encoding,
            'caracteres' => $caracteres,
            'unidades' => $unidades,
            'segmentos' => $segmentos,
        ];
    }
}
