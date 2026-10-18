<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class SmsSegmentService
{
    /**
     * GSM 03.38: alfabeto básico.
     * Cada carácter ocupa 1 septet.
     */
    private const GSM7_BASIC =
        "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ" .
        "ÆæßÉ !\"#¤%&'()*+,-./" .
        "0123456789:;<=>?" .
        "¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§" .
        "¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /**
     * GSM 03.38: tabela de extensão.
     * Cada carácter ocupa ESC + código = 2 septets.
     *
     * Form feed (\f) também pertence à extensão.
     */
    private const GSM7_EXTENDED = "^{}\\[~]|€\f";

    /**
     * Limite operacional inicial.
     *
     * Deve coincidir com o limite implementado
     * no serviço que envia as PDUs SMPP.
     */
    private const MAX_SEGMENTOS = 10;

    public function calcular(string $texto): array
    {
        if ($texto === '') {
            throw new InvalidArgumentException(
                'A mensagem SMS não pode estar vazia.'
            );
        }

        if (!mb_check_encoding($texto, 'UTF-8')) {
            throw new InvalidArgumentException(
                'A mensagem deve utilizar UTF-8 válido.'
            );
        }

        $caracteres = mb_strlen($texto, 'UTF-8');

        $septets = $this->contarSeptetsGsm7($texto);

        if ($septets !== null) {
            $segmentos = $septets <= 160
                ? 1
                : (int) ceil($septets / 153);

            $this->validarLimite($segmentos);

            return [
                'encoding' => 'GSM7',
                'caracteres' => $caracteres,
                'unidades' => $septets,
                'segmentos' => $segmentos,
            ];
        }

        /*
         * Unicode: conversão para UTF-16BE.
         *
         * Neste momento não permitimos caracteres
         * suplementares (como emojis), pois o suporte
         * UCS-2 deve ser validado com a operadora.
         */
        $chars = preg_split(
            '//u',
            $texto,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if ($chars === false) {
            throw new InvalidArgumentException(
                'Não foi possível analisar a mensagem.'
            );
        }

        foreach ($chars as $char) {
            $utf16 = mb_convert_encoding(
                $char,
                'UTF-16BE',
                'UTF-8'
            );

            if (strlen($utf16) !== 2) {
                throw new InvalidArgumentException(
                    'A mensagem contém caracteres Unicode ' .
                    'ainda não suportados, como emojis.'
                );
            }
        }

        $utf16 = mb_convert_encoding(
            $texto,
            'UTF-16BE',
            'UTF-8'
        );

        $unidades = intdiv(strlen($utf16), 2);

        $segmentos = $unidades <= 70
            ? 1
            : (int) ceil($unidades / 67);

        $this->validarLimite($segmentos);

        return [
            'encoding' => 'UNICODE',
            'caracteres' => $caracteres,
            'unidades' => $unidades,
            'segmentos' => $segmentos,
        ];
    }

    private function contarSeptetsGsm7(
        string $texto
    ): ?int {
        $chars = preg_split(
            '//u',
            $texto,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if ($chars === false) {
            return null;
        }

        $septets = 0;

        foreach ($chars as $char) {
            if (mb_strpos(
                self::GSM7_BASIC,
                $char,
                0,
                'UTF-8'
            ) !== false) {
                $septets++;
                continue;
            }

            if (mb_strpos(
                self::GSM7_EXTENDED,
                $char,
                0,
                'UTF-8'
            ) !== false) {
                $septets += 2;
                continue;
            }

            return null;
        }

        return $septets;
    }

    private function validarLimite(int $segmentos): void
    {
        if ($segmentos > self::MAX_SEGMENTOS) {
            throw new InvalidArgumentException(
                'A mensagem excede o limite de ' .
                self::MAX_SEGMENTOS .
                ' segmentos SMS.'
            );
        }
    }
}
